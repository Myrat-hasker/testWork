<?require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;

$app = Application::getInstance();
$context = $app->getContext();
$request = $context->getRequest();

global $USER;

if ($USER->isAuthorized() && Loader::IncludeModule('catalog') /*&& check_bitrix_sessid()*/)
{
	if ($request->get('action') == 'updateQuantity' && $request->isPost())
	{
		$data = $request->get('data');

		if (is_array($data) && $data['QUANTITY'])
		{
			$productIds = array_keys($data['QUANTITY']);

			if (empty($productIds))
			{
				echo json_encode(['error' => true, 'message' => 'No data!']);
				return;
			}

			$productIds = array_map(function($item) {
			    return str_replace('PRODUCT_', '', $item);
			}, $productIds);

			$products = [];
			$res = \Bitrix\Catalog\ProductTable::GetList([
				'filter' => ['ID' => $productIds],
				'select' => ['ID', 'QUANTITY'],
			]);
			while ($item = $res->fetch())
			{
			    $products[] = $item;
			}

			$storeQuantities = [];

			$res = \Bitrix\Catalog\StoreProductTable::getList([
				'filter' => ['PRODUCT_ID' => $productIds, 'STORE.ACTIVE' => 'Y'],
				'select' => ['ID', 'STORE_ID', 'PRODUCT_ID', 'AMOUNT']
			]);
			while ($item = $res->fetch())
			{
				if (!isset($storeQuantities[$item['STORE_ID']])) $storeQuantities[$item['STORE_ID']] = 0;

				$storeQuantities[$item['STORE_ID']] += $item['AMOUNT'];
			}

			$stores = [];

			$res = \Bitrix\Catalog\StoreTable::getList([
				'filter' => ['ACTIVE' => 'Y'],
			]);
			while ($item = $res->fetch())
			{
				$stores[] = [
					'ID' => $item['ID'],
					'TITLE' => $item['TITLE'],
					'QUANTITY' => $storeQuantities[$item['ID']] ?? 0,
				];
			}

			foreach ($products as $product)
			{
				$sets = [];
				$setIds = [];

				$res = CCatalogProductSet::GetList([], ['OWNER_ID' => $product['ID'], '>SET_ID' => 0], false, false, []);
			    while ($item = $res->fetch())
			    {
			    	$setIds[] = $item['ITEM_ID'];
			    	$sets[] = $item;
			    }

			    $res = \Bitrix\Catalog\ProductTable::GetList([
					'filter' => ['ID' => $setIds],
					'select' => ['ID', 'NAME' => 'IBLOCK_ELEMENT.NAME', 'QUANTITY'],
				]);
				while ($item = $res->fetch())
				{
				    if (isset(array_flip($setIds)[$item['ID']]))
			    	{
			    		$sets[array_flip($setIds)[$item['ID']]]['NAME'] = $item['NAME'];
			    		$sets[array_flip($setIds)[$item['ID']]]['CATALOG_QUANTITY'] = $item['QUANTITY'];
			    	}
				}

				foreach ($data['QUANTITY']['PRODUCT_'.$product['ID']] as $storeId => $quantity)
				{
					$storeId = str_replace('STORE_', '', $storeId);

					if ($request->get('type') == 'add')
						$quantity = ($product['QUANTITY'] + $quantity);

					if ($storeId == 'MAIN')
					{
						$arFields = [
					        // 'QUANTITY' => $product['QUANTITY'] + $quantity,
					        'QUANTITY' => $quantity,
					        // 'AVAILABLE' => ($product['QUANTITY'] + $quantity) > 0 ? 'Y' : 'N',
					        'AVAILABLE' => $quantity > 0 ? 'Y' : 'N',
					    ];

					    $setsCount = [];
					    $insufficientSets = [];

					    foreach ($sets as $set)
					    {
					    	$setsCount[$set['ITEM_ID']] = $set['CATALOG_QUANTITY'] - ($set['QUANTITY'] * ($quantity - $product['QUANTITY']));

					    	if ($setsCount[$set['ITEM_ID']] < 0)
					    		$insufficientSets[] = $set['ITEM_ID'];
					    }

					    if ($quantity < 0)
					    {
					    	echo json_encode(['error' => true, 'message' => 'Ошибка: остаток должен быть больше или равен нулю']);
							return;
					    }

					    if ($product['QUANTITY'] > $quantity)
					    {
					    	echo json_encode(['error' => true, 'message' => 'Ошибка: попытка списать комплкет']);
							return;
					    }

					    if ($product['QUANTITY'] == $quantity)
					    {
					    	echo json_encode(['error' => true, 'message' => 'Остаток не изменен']);
							return;
					    }

					    if (!empty($insufficientSets))
					    {
					    	echo json_encode(['error' => true, 'message' => 'Ошибка: не достаточно остатков комплектующих', 'itemIds' => $insufficientSets, 'cnts' => $setsCount]);
							return;
					    }

					    $assemblies[] = [
							'PRODUCT_ID' => $product['ID'],
							'AMOUNT' => ($quantity - $product['QUANTITY']),
						];

					    $DB->StartTransaction();

						$result = \Bitrix\Catalog\ProductTable::Update($product['ID'], $arFields);
						if (!$result->isSuccess())
						{
							$DB->Rollback();
							echo json_encode(['error' => true, 'message' => $result->getErrorMessages()]);
							return;
						}
						else
						{
							foreach ($setsCount as $id => $cnt)
							{
								$arFields = [
							        'QUANTITY' => $cnt,
							        'AVAILABLE' => $cnt > 0 ? 'Y' : 'N',
							    ];

								$result = \Bitrix\Catalog\ProductTable::Update($id, $arFields);
								if (!$result->isSuccess())
								{
									$DB->Rollback();
									echo json_encode(['error' => true, 'message' => $result->getErrorMessages()]);
									return;
								}
							}

							$res = CIBlock::GetList([], ['TYPE' => 'catalog', 'CODE' => 'assembly'], false);
							if ($iblock = $res->fetch())
							{
								foreach ($assemblies as $item)
								{
									$el = new CIBlockElement;

									$arFields = [
										'IBLOCK_ID' => $iblock['ID'],
										'ACTIVE' => 'Y',
										'NAME' => 'Сборка от ' . date('d.m.Y H:i:s') . ' ' . $USER->getFullName(),
										'CREATED_BY' => $USER->GetID(),
										'PROPERTY_VALUES' => [
											'PRODUCT' => $item['PRODUCT_ID'],
											'AMOUNT' => $item['AMOUNT'],
										]
									];

									$el->Add($arFields);
								}
							}
						}

						$DB->Commit();
					}
					else
					{
						$arFields = [
					        "PRODUCT_ID" => $product['ID'],
					        "STORE_ID" => $storeId,
					        // "AMOUNT" => (int) $storeQuantities[$storeId] + $quantity
					        "AMOUNT" => $quantity
					    ];
					    if (!CCatalogStoreProduct::UpdateFromForm($arFields))
					    {
					    	echo json_encode(['error' => true, 'message' => 'Error updating store ' . $storeId]);
							return;
					    }
					}
				}
			}

			// get actual quantity after update
			$quantities = [];
			$res = \Bitrix\Catalog\ProductTable::GetList([
				'filter' => ['ID' => $productIds],
				'select' => ['ID', 'QUANTITY'],
			]);
			while ($item = $res->fetch())
			{
			    $quantities['catalog_item_' . $item['ID'] . '_quantity'] = $item['QUANTITY'];
			}

			echo json_encode(['success' => true, 'quantities' => $quantities]);
			return;
		}

		echo json_encode(['error' => true, 'message' => 'Invalid request!']);
		return;
	}

	if ($request->get('action') == 'writeOff' && $request->isPost())
	{
		$data = $request->get('data');

		if (is_array($data) && $data['WRITEOFFS'] && $data['COMMENTS'])
		{
			$productIds = array_keys($data['WRITEOFFS']);

			if (empty($productIds))
			{
				echo json_encode(['error' => true, 'message' => 'No data!']);
				return;
			}

			$productIds = array_map(function($item) {
			    return str_replace('PRODUCT_', '', $item);
			}, $productIds);

			$products = [];
			$res = \Bitrix\Catalog\ProductTable::GetList([
				'filter' => ['ID' => $productIds],
				'select' => ['ID', 'QUANTITY'],
			]);
			while ($item = $res->fetch())
			{
			    $products[] = $item;
			}

			$storeQuantities = [];

			$res = \Bitrix\Catalog\StoreProductTable::getList([
				'filter' => ['PRODUCT_ID' => $productIds, 'STORE.ACTIVE' => 'Y'],
				'select' => ['ID', 'STORE_ID', 'PRODUCT_ID', 'AMOUNT']
			]);
			while ($item = $res->fetch())
			{
				if (!isset($storeQuantities[$item['STORE_ID']])) $storeQuantities[$item['STORE_ID']] = 0;

				$storeQuantities[$item['STORE_ID']] += $item['AMOUNT'];
			}

			$stores = [];

			$res = \Bitrix\Catalog\StoreTable::getList([
				'filter' => ['ACTIVE' => 'Y'],
			]);
			while ($item = $res->fetch())
			{
				$stores[] = [
					'ID' => $item['ID'],
					'TITLE' => $item['TITLE'],
					'QUANTITY' => $storeQuantities[$item['ID']] ?? 0,
				];
			}

			$writeoffs = [];

			foreach ($products as $product)
			{
				foreach ($data['WRITEOFFS']['PRODUCT_'.$product['ID']] as $storeId => $quantity)
				{
					if (strlen(trim($quantity)) < 1) continue;

					$comment = $data['COMMENTS']['PRODUCT_'.$product['ID']][$storeId];

					$storeId = str_replace('STORE_', '', $storeId);

					if ($storeId == 'MAIN')
					{
						$arFields = [
					        'QUANTITY' => ($product['QUANTITY'] - $quantity),
					        'AVAILABLE' => ($product['QUANTITY'] - $quantity) > 0 ? 'Y' : 'N',
					    ];

					    if ($arFields['QUANTITY'] < 0)
					    {

					    	echo json_encode(['error' => true, 'message' => 'Ошибка: текущий остаток ' . $product['QUANTITY'] . ' прпытка списать ' . $quantity, 'productId' => $product['ID'], 'storeId' => $storeId]);
							return;
					    }

					    if ($quantity < 1)
					    {
					    	echo json_encode(['error' => true, 'message' => 'Ошибка: введите количество для списания', 'productId' => $product['ID'], 'storeId' => $storeId]);
							return;
					    }

						$result = \Bitrix\Catalog\ProductTable::Update($product['ID'], $arFields);
						if (!$result->isSuccess())
						{
							echo json_encode(['error' => true, 'message' => $result->getErrorMessages()]);
							return;
						}

						$writeoffs[] = [
							'PRODUCT_ID' => $product['ID'],
							'AMOUNT' => $quantity,
							'COMMENT' => $comment,
						];
					}
					else
					{
						$arFields = [
					        "PRODUCT_ID" => $product['ID'],
					        "STORE_ID" => $storeId,
					        // "AMOUNT" => (int) $storeQuantities[$storeId] + $quantity
					        "AMOUNT" => $storeQuantities[$storeId] - $quantity
					    ];
					    if (!CCatalogStoreProduct::UpdateFromForm($arFields))
					    {
					    	echo json_encode(['error' => true, 'message' => 'Error updating store ' . $storeId]);
							return;
					    }

					    $writeoffs[] = [
							'PRODUCT_ID' => $product['ID'],
							'AMOUNT' => $quantity,
							'COMMENT' => $comment,
						];
					}
				}
			}

			if (!empty($writeoffs))
			{
				$res = CIBlock::GetList([], ['TYPE' => 'catalog', 'CODE' => 'writeoffs'], false);
				if ($iblock = $res->fetch())
				{
					foreach ($writeoffs as $item)
					{
						$el = new CIBlockElement;

						$arFields = [
							'IBLOCK_ID' => $iblock['ID'],
							'ACTIVE' => 'Y',
							'NAME' => 'Списание от ' . date('d.m.Y H:i:s'),
							'CREATED_BY' => $USER->GetID(),
							'PROPERTY_VALUES' => [
								'PRODUCT' => $item['PRODUCT_ID'],
								'AMOUNT' => $item['AMOUNT'],
								'COMMENT' => $item['COMMENT'],
							]
						];

						$el->Add($arFields);
					}
				}
			}

			echo json_encode(['success' => true]);
			return;
		}

		echo json_encode(['error' => true, 'message' => 'Invalid request!']);
		return;
	}

	if ($request->get('modal') == 1)
	{
		$itemId = (int) $request->get('itemId');
		$data = $request->get('data');

		if ($itemId < 1)
		{
			echo json_encode(['error' => true, 'message' => 'No data!']);
			return;
		}

		$arProduct = [];

		$res = \Bitrix\Catalog\ProductTable::GetList([
			'filter' => ['ID' => $itemId],
			'select' => ['ID', 'QUANTITY'],
		]);
		if ($item = $res->fetch())
		{
		    $arProduct = $item;
		}

		if (empty($arProduct))
		{
			echo json_encode(['error' => true, 'message' => 'Product not found!']);
			return;
		}

		$storeQuantities = [];

		$res = \Bitrix\Catalog\StoreProductTable::getList([
			'filter' => ['=PRODUCT_ID' => $arProduct['ID'], 'STORE.ACTIVE' => 'Y'],
			'select' => ['ID', 'STORE_ID', 'PRODUCT_ID', 'AMOUNT']
		]);
		while ($item = $res->fetch())
		{
			if (!isset($storeQuantities[$item['STORE_ID']])) $storeQuantities[$item['STORE_ID']] = 0;

			$storeQuantities[$item['STORE_ID']] += $item['AMOUNT'];
		}

		$stores = [
			[
				'ID' => 'MAIN',
				'TITLE' => 'Основной',
				'QUANTITY' => $arProduct['QUANTITY'] ?? 0
			]
		];
		$res = \Bitrix\Catalog\StoreTable::getList([
			'filter' => ['ACTIVE' => 'Y'],
		]);
		while ($item = $res->fetch())
		{
			$stores[] = [
				'ID' => $item['ID'],
				'TITLE' => $item['TITLE'],
				'QUANTITY' => $storeQuantities[$item['ID']] ?? 0,
			];
		}

		$sets = [];
		$productIds = [];

		$res = CCatalogProductSet::GetList([], ['OWNER_ID' => $arProduct['ID'], '>SET_ID' => 0], false, false, []);
	    while ($item = $res->fetch())
	    {
	    	$productIds[] = $item['ITEM_ID'];
	    	$sets[] = $item;
	    }

	    if (empty($productIds))
	    {
	    	echo json_encode(['error' => true, 'message' => 'У данного продукта нет комплектующих']);
			return;
	    }

	    $res = CIblockElement::GetList([], ['ID' => $productIds], false, false, ['ID', 'NAME', 'CATALOG_QUANTITY']);
	    while ($item = $res->fetch())
	    {
	    	if (isset(array_flip($productIds)[$item['ID']]))
	    	{
	    		$sets[array_flip($productIds)[$item['ID']]]['NAME'] = $item['NAME'];
	    		$sets[array_flip($productIds)[$item['ID']]]['CATALOG_QUANTITY'] = $item['CATALOG_QUANTITY'];
	    	}
	    }

		ob_start();
		?>
		<div class="container">
			<?foreach($stores as $store):?>
				<div class="row">
					<div class="col-sm">
						<div class="product-item-amount-field-container" data-entity="quantity-block">
							<span class="product-item-amount-field-btn-minus no-select modal-view<?if(!$data || $data['amnt'] && $data['amnt'] < 1):?> disabled<?endif;?>">
								<svg width="14" height="2" viewBox="0 0 14 2" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path d="M0 2V0H14V2H0Z" fill="white" />
								</svg>
							</span>
							<input class="product-item-amount-field" type="text" value="<?=($data && $data['amnt'] && $data['amnt'] > 0) ? $store['QUANTITY'] + $data['amnt'] : $store['QUANTITY']?>" min-value="<?=$store['QUANTITY']?>" name="QUANTITY[PRODUCT_<?=$arProduct['ID']?>][STORE_<?=$store['ID']?>]" onkeypress='return event.charCode >= 48 && event.charCode <= 57'>
							<span class="product-item-amount-field-btn-plus no-select modal-view">
								<svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path d="M5.87692 14V8.12308H0V5.86154H5.87692V0H8.13846V5.86154H14V8.12308H8.13846V14H5.87692Z" fill="white" />
								</svg>
							</span>
						</div>
					</div>
					<div class="col-sm">
						<span class="catalog-modal-btn catalog-modal-btn-secendory">
							<p><?=$store['TITLE']?></p>
						</span>
					</div>
					<div class="col-sm">
						<span class="catalog-modal-btn catalog-modal-btn-secendory open-catalog-item-child-modal" product-id="<?=$arProduct['ID']?>" store-id="<?=$store['ID']?>">
							<p>Списание&nbsp;брака</p>
						</span>
					</div>
				</div>
			<?endforeach;?>
			<table class="sets-table">
				<tr>
					<th>Наименование</th>
					<th>Количество в комплекте</th>
					<th>Oстаток на складе</th>
					<th></th>
				</tr>
				<?foreach($sets as $set):?>
					<tr item-id="<?=$set['ITEM_ID']?>" <?if($data && $data['itemIds'] && is_array($data['itemIds']) && in_array($set['ITEM_ID'], $data['itemIds']) && $data['cnts'] && $data['cnts'][$set['ITEM_ID']]):?>class="highlighted"<?endif;?>>
						<td><?=$set['NAME']?></td>
						<td><?=$set['QUANTITY']?></td>
						<td><?=$set['CATALOG_QUANTITY']?></td>
						<td class="insufficient"><?=($data && $data['itemIds'] && is_array($data['itemIds']) && in_array($set['ITEM_ID'], $data['itemIds']) && $data['cnts'] && $data['cnts'][$set['ITEM_ID']] ? $data['cnts'][$set['ITEM_ID']] : '')?></td>
					</tr>
				<?endforeach;?>
			</table>
			<?
			// echo '<pre>';
			// print_r($arProduct);
			// print_r($sets);
			// echo '</pre>';
			?>
		</div>
		<?
		$content = ob_get_clean();

		echo json_encode(['success' => true, 'article' => 'Артикул: <b>' . $arProduct['ID'] . '</b>', 'content' => $content]);
		return;
	}

	if ($request->get('modal') == 2)
	{
		$productId = (int) $request->get('productId');
		$storeId = $request->get('storeId');

		if ($productId < 1 || empty($storeId))
		{
			echo json_encode(['error' => true, 'message' => 'No data!']);
			return;
		}

		$arProduct = [];

		$res = \Bitrix\Catalog\ProductTable::GetList([
			'filter' => ['ID' => $productId],
			'select' => ['ID', 'QUANTITY'],
		]);
		if ($item = $res->fetch())
		{
		    $arProduct = $item;
		}

		if (empty($arProduct))
		{
			echo json_encode(['error' => true, 'message' => 'Product not found!']);
			return;
		}

		$res = CIblockElement::GetByID($arProduct['ID']);
		if ($item = $res->fetch())
		{
			$arProduct['NAME'] = $item['NAME'];
		}

		if ($storeId !== 'MAIN')
		{
			$res = \Bitrix\Catalog\StoreTable::getList([
				'filter' => ['ID' => $storeId, 'ACTIVE' => 'Y'],
			]);
			if (!$sore = $res->fetch())
			{
				echo json_encode(['error' => true, 'message' => 'Store not found!']);
				return;
			}
		}
		

		$sets = [];
		$productIds = [];

	    $res = CCatalogProductSet::GetList([], ['OWNER_ID' => $arProduct['ID'], '>SET_ID' => 0], false, false, []);
	    while ($item = $res->fetch())
	    {
	    	$productIds[] = $item['ITEM_ID'];
	    	$sets[] = $item;
	    }

	    if (empty($productIds))
	    {
	    	echo json_encode(['error' => true, 'message' => 'У данного продукта нет комплектующих']);
			return;
	    }

	    $res = CIblockElement::GetList([], ['ID' => $productIds], false, false, []);
	    while ($item = $res->fetch())
	    {
	    	if (isset(array_flip($productIds)[$item['ID']]))
	    	{
	    		$sets[array_flip($productIds)[$item['ID']]]['NAME'] = $item['NAME'];
	    	}
	    }

		ob_start();
		?>
		<table>
			<tr>
				<th>Наименование</th>
				<th>К списанию</th>
				<th>Комментарий</th>
			</tr>
			<?foreach($sets as $set):?>
			<tr>
				<td><?=$set['NAME']?></td>
				<td><input class="modal-write-off-field" type="text" name="WRITEOFFS[PRODUCT_<?=$set['ITEM_ID']?>][STORE_<?=$storeId?>]" onkeypress='return event.charCode >= 48 && event.charCode <= 57'></td>
				<td><input class="modal-comment-field" name="COMMENTS[PRODUCT_<?=$set['ITEM_ID']?>][STORE_<?=$storeId?>]" type="text"></td>
			</tr>
			<?endforeach;?>
		</table>
		<?
		$content = ob_get_clean();

		echo json_encode(['success' => true, 'productName' => $arProduct['NAME'], 'content' => $content]);
		return;
	}

	echo json_encode(['error' => true, 'message' => 'Invalid request!']);
	return;
}

echo json_encode(['error' => true, 'message' => 'Request validation error!']);
return;