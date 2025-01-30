<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Catalog\ProductTable;
use Bitrix\Main\Config\Option;

/**
 * @global CMain $APPLICATION
 * @var array $arParams
 * @var array $arResult
 * @var CatalogSectionComponent $component
 * @var CBitrixComponentTemplate $this
 * @var string $templateName
 * @var string $componentPath
 *
 *  _________________________________________________________________________
 * |	Attention!
 * |	The following comments are for system use
 * |	and are required for the component to work correctly in ajax mode:
 * |	<!-- items-container -->
 * |	<!-- pagination-container -->
 * |	<!-- component-end -->
 */

$this->setFrameMode(true);

$buyDisabled = Option::get('my.custom.module', 'catalog_buy_disabled', 'N');

\Bitrix\Main\UI\Extension::load("ui.bootstrap4");
\Bitrix\Main\UI\Extension::load("ui.notification");

if (!empty($arResult['NAV_RESULT'])) {
	$navParams =  array(
		'NavPageCount' => $arResult['NAV_RESULT']->NavPageCount,
		'NavPageNomer' => $arResult['NAV_RESULT']->NavPageNomer,
		'NavNum' => $arResult['NAV_RESULT']->NavNum
	);
} else {
	$navParams = array(
		'NavPageCount' => 1,
		'NavPageNomer' => 1,
		'NavNum' => $this->randString()
	);
}

$showTopPager = false;
$showBottomPager = false;
$showLazyLoad = false;

if ($arParams['PAGE_ELEMENT_COUNT'] > 0 && $navParams['NavPageCount'] > 1) {
	$showTopPager = $arParams['DISPLAY_TOP_PAGER'];
	$showBottomPager = $arParams['DISPLAY_BOTTOM_PAGER'];
	$showLazyLoad = $arParams['LAZY_LOAD'] === 'Y' && $navParams['NavPageNomer'] != $navParams['NavPageCount'];
}

$templateLibrary = array('popup', 'ajax', 'fx');
$currencyList = '';

if (!empty($arResult['CURRENCIES'])) {
	$templateLibrary[] = 'currency';
	$currencyList = CUtil::PhpToJSObject($arResult['CURRENCIES'], false, true, true);
}

$templateData = array(
	'TEMPLATE_THEME' => $arParams['TEMPLATE_THEME'],
	'TEMPLATE_LIBRARY' => $templateLibrary,
	'CURRENCIES' => $currencyList,
	'USE_PAGINATION_CONTAINER' => $showTopPager || $showBottomPager,
);
unset($currencyList, $templateLibrary);

$elementEdit = CIBlock::GetArrayByID($arParams['IBLOCK_ID'], 'ELEMENT_EDIT');
$elementDelete = CIBlock::GetArrayByID($arParams['IBLOCK_ID'], 'ELEMENT_DELETE');
$elementDeleteParams = array('CONFIRM' => GetMessage('CT_BCS_TPL_ELEMENT_DELETE_CONFIRM'));

$positionClassMap = array(
	'left' => 'product-item-label-left',
	'center' => 'product-item-label-center',
	'right' => 'product-item-label-right',
	'bottom' => 'product-item-label-bottom',
	'middle' => 'product-item-label-middle',
	'top' => 'product-item-label-top'
);

$discountPositionClass = '';
if ($arParams['SHOW_DISCOUNT_PERCENT'] === 'Y' && !empty($arParams['DISCOUNT_PERCENT_POSITION'])) {
	foreach (explode('-', $arParams['DISCOUNT_PERCENT_POSITION']) as $pos) {
		$discountPositionClass .= isset($positionClassMap[$pos]) ? ' ' . $positionClassMap[$pos] : '';
	}
}

$labelPositionClass = '';
if (!empty($arParams['LABEL_PROP_POSITION'])) {
	foreach (explode('-', $arParams['LABEL_PROP_POSITION']) as $pos) {
		$labelPositionClass .= isset($positionClassMap[$pos]) ? ' ' . $positionClassMap[$pos] : '';
	}
}

$arParams['~MESS_BTN_BUY'] = ($arParams['~MESS_BTN_BUY'] ?? '') ?: Loc::getMessage('CT_BCS_TPL_MESS_BTN_BUY');
$arParams['~MESS_BTN_DETAIL'] = ($arParams['~MESS_BTN_DETAIL'] ?? '') ?: Loc::getMessage('CT_BCS_TPL_MESS_BTN_DETAIL');
$arParams['~MESS_BTN_COMPARE'] = ($arParams['~MESS_BTN_COMPARE'] ?? '') ?: Loc::getMessage('CT_BCS_TPL_MESS_BTN_COMPARE');
$arParams['~MESS_BTN_SUBSCRIBE'] = ($arParams['~MESS_BTN_SUBSCRIBE'] ?? '') ?: Loc::getMessage('CT_BCS_TPL_MESS_BTN_SUBSCRIBE');
$arParams['~MESS_BTN_ADD_TO_BASKET'] = ($arParams['~MESS_BTN_ADD_TO_BASKET'] ?? '') ?: Loc::getMessage('CT_BCS_TPL_MESS_BTN_ADD_TO_BASKET');
$arParams['~MESS_NOT_AVAILABLE'] = ($arParams['~MESS_NOT_AVAILABLE'] ?? '') ?: Loc::getMessage('CT_BCS_TPL_MESS_PRODUCT_NOT_AVAILABLE');
$arParams['~MESS_NOT_AVAILABLE_SERVICE'] = ($arParams['~MESS_NOT_AVAILABLE_SERVICE'] ?? '') ?: Loc::getMessage('CP_BCS_TPL_MESS_PRODUCT_NOT_AVAILABLE_SERVICE');
$arParams['~MESS_SHOW_MAX_QUANTITY'] = ($arParams['~MESS_SHOW_MAX_QUANTITY'] ?? '') ?: Loc::getMessage('CT_BCS_CATALOG_SHOW_MAX_QUANTITY');
$arParams['~MESS_RELATIVE_QUANTITY_MANY'] = ($arParams['~MESS_RELATIVE_QUANTITY_MANY'] ?? '') ?: Loc::getMessage('CT_BCS_CATALOG_RELATIVE_QUANTITY_MANY');
$arParams['MESS_RELATIVE_QUANTITY_MANY'] = ($arParams['MESS_RELATIVE_QUANTITY_MANY'] ?? '') ?: Loc::getMessage('CT_BCS_CATALOG_RELATIVE_QUANTITY_MANY');
$arParams['~MESS_RELATIVE_QUANTITY_FEW'] = ($arParams['~MESS_RELATIVE_QUANTITY_FEW'] ?? '') ?: Loc::getMessage('CT_BCS_CATALOG_RELATIVE_QUANTITY_FEW');
$arParams['MESS_RELATIVE_QUANTITY_FEW'] = ($arParams['MESS_RELATIVE_QUANTITY_FEW'] ?? '') ?: Loc::getMessage('CT_BCS_CATALOG_RELATIVE_QUANTITY_FEW');

$arParams['MESS_BTN_LAZY_LOAD'] = $arParams['MESS_BTN_LAZY_LOAD'] ?: Loc::getMessage('CT_BCS_CATALOG_MESS_BTN_LAZY_LOAD');

$obName = 'ob' . preg_replace('/[^a-zA-Z0-9_]/', 'x', $this->GetEditAreaId($navParams['NavNum']));
$containerName = 'container-' . $navParams['NavNum'];



$generalParams = [
	'SHOW_DISCOUNT_PERCENT' => $arParams['SHOW_DISCOUNT_PERCENT'],
	'PRODUCT_DISPLAY_MODE' => $arParams['PRODUCT_DISPLAY_MODE'],
	'SHOW_MAX_QUANTITY' => $arParams['SHOW_MAX_QUANTITY'],
	'RELATIVE_QUANTITY_FACTOR' => $arParams['RELATIVE_QUANTITY_FACTOR'],
	'MESS_SHOW_MAX_QUANTITY' => $arParams['~MESS_SHOW_MAX_QUANTITY'],
	'MESS_RELATIVE_QUANTITY_MANY' => $arParams['~MESS_RELATIVE_QUANTITY_MANY'],
	'MESS_RELATIVE_QUANTITY_FEW' => $arParams['~MESS_RELATIVE_QUANTITY_FEW'],
	'SHOW_OLD_PRICE' => $arParams['SHOW_OLD_PRICE'],
	'USE_PRODUCT_QUANTITY' => $arParams['USE_PRODUCT_QUANTITY'],
	'PRODUCT_QUANTITY_VARIABLE' => $arParams['PRODUCT_QUANTITY_VARIABLE'],
	'ADD_TO_BASKET_ACTION' => $arParams['ADD_TO_BASKET_ACTION'],
	'ADD_PROPERTIES_TO_BASKET' => $arParams['ADD_PROPERTIES_TO_BASKET'],
	'PRODUCT_PROPS_VARIABLE' => $arParams['PRODUCT_PROPS_VARIABLE'],
	'SHOW_CLOSE_POPUP' => $arParams['SHOW_CLOSE_POPUP'],
	'DISPLAY_COMPARE' => $arParams['DISPLAY_COMPARE'],
	'COMPARE_PATH' => $arParams['COMPARE_PATH'],
	'COMPARE_NAME' => $arParams['COMPARE_NAME'],
	'PRODUCT_SUBSCRIPTION' => $arParams['PRODUCT_SUBSCRIPTION'],
	'PRODUCT_BLOCKS_ORDER' => $arParams['PRODUCT_BLOCKS_ORDER'],
	'LABEL_POSITION_CLASS' => $labelPositionClass,
	'DISCOUNT_POSITION_CLASS' => $discountPositionClass,
	'SLIDER_INTERVAL' => $arParams['SLIDER_INTERVAL'],
	'SLIDER_PROGRESS' => $arParams['SLIDER_PROGRESS'],
	'~BASKET_URL' => $arParams['~BASKET_URL'],
	'~ADD_URL_TEMPLATE' => $arResult['~ADD_URL_TEMPLATE'],
	'~BUY_URL_TEMPLATE' => $arResult['~BUY_URL_TEMPLATE'],
	'~COMPARE_URL_TEMPLATE' => $arResult['~COMPARE_URL_TEMPLATE'],
	'~COMPARE_DELETE_URL_TEMPLATE' => $arResult['~COMPARE_DELETE_URL_TEMPLATE'],
	'TEMPLATE_THEME' => $arParams['TEMPLATE_THEME'],
	'USE_ENHANCED_ECOMMERCE' => $arParams['USE_ENHANCED_ECOMMERCE'],
	'DATA_LAYER_NAME' => $arParams['DATA_LAYER_NAME'],
	'BRAND_PROPERTY' => $arParams['BRAND_PROPERTY'],
	'MESS_BTN_BUY' => $arParams['~MESS_BTN_BUY'],
	'MESS_BTN_DETAIL' => $arParams['~MESS_BTN_DETAIL'],
	'MESS_BTN_COMPARE' => $arParams['~MESS_BTN_COMPARE'],
	'MESS_BTN_SUBSCRIBE' => $arParams['~MESS_BTN_SUBSCRIBE'],
	'MESS_BTN_ADD_TO_BASKET' => $arParams['~MESS_BTN_ADD_TO_BASKET'],
];

$areaIds = [];
$itemParameters = [];

foreach ($arResult['ITEMS'] as $item) {
	$uniqueId = $item['ID'] . '_' . md5($this->randString() . $component->getAction());
	$areaIds[$item['ID']] = $this->GetEditAreaId($uniqueId);
	$this->AddEditAction($uniqueId, $item['EDIT_LINK'], $elementEdit);
	$this->AddDeleteAction($uniqueId, $item['DELETE_LINK'], $elementDelete, $elementDeleteParams);

	$itemParameters[$item['ID']] = [
		'SKU_PROPS' => $arResult['SKU_PROPS'][$item['IBLOCK_ID']],
		'MESS_NOT_AVAILABLE' => ($arResult['MODULES']['catalog'] && $item['PRODUCT']['TYPE'] === ProductTable::TYPE_SERVICE
			? $arParams['~MESS_NOT_AVAILABLE_SERVICE']
			: $arParams['~MESS_NOT_AVAILABLE']
		),
	];
}
?>
<!-- items-container -->

<div class="catalog_sections picker">

	<?
	foreach ($arResult['ITEM_ROWS'] as $rowData) {
		$rowItems = array_splice($arResult['ITEMS'], 0, $rowData['COUNT']);

		foreach ($rowItems as $item) {

			$APPLICATION->IncludeComponent(
				'bitrix:catalog.item',
				'picker',
				array(
					'RESULT' => array(
						'ITEM' => $item,
						'AREA_ID' => $areaIds[$item['ID']],
						'TYPE' => 'card',
						'BIG_LABEL' => 'N',
						'BIG_DISCOUNT_PERCENT' => 'N',
						'BIG_BUTTONS' => 'Y',
						'SCALABLE' => 'N'
					),
					'PARAMS' => $generalParams + $itemParameters[$item['ID']],
					'STORES' => $arParams['STORES']
				),
				$component,
				array('HIDE_ICONS' => 'Y')
			);
		}
	}
	unset($rowItems);
	unset($itemParameters);
	unset($areaIds);
	unset($generalParams);
	?>
</div>

<div class="picker_update_all" style="display: none">Обновить указанные товары</div>

<? if ($showBottomPager) {
?>
	<div data-pagination-num="<?= $navParams['NavNum'] ?>">
		<!-- pagination-container -->
		<?= $arResult['NAV_STRING'] ?>
		<!-- pagination-container -->
	</div>
<?
}

$signer = new \Bitrix\Main\Security\Sign\Signer;
$signedTemplate = $signer->sign($templateName, 'catalog.section');
$signedParams = $signer->sign(base64_encode(serialize($arResult['ORIGINAL_PARAMETERS'])), 'catalog.section');
?>
<script>
	BX.message({
		BTN_MESSAGE_BASKET_REDIRECT: '<?= GetMessageJS('CT_BCS_CATALOG_BTN_MESSAGE_BASKET_REDIRECT') ?>',
		BASKET_URL: '<?= $arParams['BASKET_URL'] ?>',
		ADD_TO_BASKET_OK: '<?= GetMessageJS('ADD_TO_BASKET_OK') ?>',
		TITLE_ERROR: '<?= GetMessageJS('CT_BCS_CATALOG_TITLE_ERROR') ?>',
		TITLE_BASKET_PROPS: '<?= GetMessageJS('CT_BCS_CATALOG_TITLE_BASKET_PROPS') ?>',
		TITLE_SUCCESSFUL: '<?= GetMessageJS('ADD_TO_BASKET_OK') ?>',
		BASKET_UNKNOWN_ERROR: '<?= GetMessageJS('CT_BCS_CATALOG_BASKET_UNKNOWN_ERROR') ?>',
		BTN_MESSAGE_SEND_PROPS: '<?= GetMessageJS('CT_BCS_CATALOG_BTN_MESSAGE_SEND_PROPS') ?>',
		BTN_MESSAGE_CLOSE: '<?= GetMessageJS('CT_BCS_CATALOG_BTN_MESSAGE_CLOSE') ?>',
		BTN_MESSAGE_CLOSE_POPUP: '<?= GetMessageJS('CT_BCS_CATALOG_BTN_MESSAGE_CLOSE_POPUP') ?>',
		COMPARE_MESSAGE_OK: '<?= GetMessageJS('CT_BCS_CATALOG_MESS_COMPARE_OK') ?>',
		COMPARE_UNKNOWN_ERROR: '<?= GetMessageJS('CT_BCS_CATALOG_MESS_COMPARE_UNKNOWN_ERROR') ?>',
		COMPARE_TITLE: '<?= GetMessageJS('CT_BCS_CATALOG_MESS_COMPARE_TITLE') ?>',
		PRICE_TOTAL_PREFIX: '<?= GetMessageJS('CT_BCS_CATALOG_PRICE_TOTAL_PREFIX') ?>',
		RELATIVE_QUANTITY_MANY: '<?= CUtil::JSEscape($arParams['MESS_RELATIVE_QUANTITY_MANY']) ?>',
		RELATIVE_QUANTITY_FEW: '<?= CUtil::JSEscape($arParams['MESS_RELATIVE_QUANTITY_FEW']) ?>',
		BTN_MESSAGE_COMPARE_REDIRECT: '<?= GetMessageJS('CT_BCS_CATALOG_BTN_MESSAGE_COMPARE_REDIRECT') ?>',
		BTN_MESSAGE_LAZY_LOAD: '<?= CUtil::JSEscape($arParams['MESS_BTN_LAZY_LOAD']) ?>',
		BTN_MESSAGE_LAZY_LOAD_WAITER: '<?= GetMessageJS('CT_BCS_CATALOG_BTN_MESSAGE_LAZY_LOAD_WAITER') ?>',
		SITE_ID: '<?= CUtil::JSEscape($component->getSiteId()) ?>'
	});
	var <?= $obName ?> = new JCCatalogSectionComponent({
		siteId: '<?= CUtil::JSEscape($component->getSiteId()) ?>',
		componentPath: '<?= CUtil::JSEscape($componentPath) ?>',
		navParams: <?= CUtil::PhpToJSObject($navParams) ?>,
		deferredLoad: false, // enable it for deferred load
		initiallyShowHeader: '<?= !empty($arResult['ITEM_ROWS']) ?>',
		bigData: <?= CUtil::PhpToJSObject($arResult['BIG_DATA']) ?>,
		lazyLoad: !!'<?= $showLazyLoad ?>',
		loadOnScroll: !!'<?= ($arParams['LOAD_ON_SCROLL'] === 'Y') ?>',
		template: '<?= CUtil::JSEscape($signedTemplate) ?>',
		ajaxId: '<?= CUtil::JSEscape($arParams['AJAX_ID'] ?? '') ?>',
		parameters: '<?= CUtil::JSEscape($signedParams) ?>',
		container: '<?= $containerName ?>'
	});
</script>

<style type="text/css">
	.modal {
		text-align: center;
	}

	@media screen and (min-width: 768px) { 
		.catalog-item-modal.modal:before {
			display: inline-block;
			vertical-align: middle;
			content: " ";
			height: 100%;
		}
	}

	.catalog-item-modal .modal-dialog {
		display: inline-block;
		text-align: left;
		vertical-align: middle;
		max-width: unset;
	}

	.catalog-item-modal .catalog-modal-btn > p {
		text-align: center;
	    vertical-align: middle;
	    display: table-cell;
	}

	.catalog-item-modal .product-item-amount-field-container input {
		width: 50px;
	}

	.catalog-item-modal .modal-footer {
		text-align: center;
	    display: flex;
	    flex-direction: column;
	}

	.catalog-modal-btn {
		display: table;
		width: 100%;
		height: 39px;
		border-radius: 5px;
		background-color: #28217B;
		color: #fff;
		text-align: center;
		cursor: pointer;
		padding: 0px 20px;
		border: none;
		max-width: 400px;
	}

	.catalog-item-modal .modal-header > .modal-subtitle-block {
		display: flex;
	    width: 100%;
	    justify-content: space-between;
	}

	.catalog-item-modal .modal-subtitle {
		color: #28217B;
		font-size: 14px;
	}

	.catalog-item-modal .modal-title.modal-title-grid {
		display: grid;
	}

	button.catalog-modal-btn:focus { outline: none; }
	input.modal-write-off-field:focus { outline: none; }
	input.modal-comment-field:focus { outline: none; }

	.catalog-item-modal .modal-field-error {
		border-color: #f71616
	}

	.catalog-modal-btn-secendory {
		background-color: #ffffff;
		border: 2px solid #28217B;
		color: #28217B;
	}

	.product-item-amount-field-btn-plus.modal-view, .product-item-amount-field-btn-minus.modal-view {
		background-color: #28217B;
		width: 53px;
	}

	.product-item-amount-field-btn-plus.modal-view.disabled, .product-item-amount-field-btn-minus.modal-view.disabled {
		pointer-events: none;
    	background-color: #c8c8c8;
	}

	.catalog-item-modal table {

	}

	.catalog-item-modal table th {
		color: #B4B4B4;
    	font-size: 14px;
	}

	.catalog-item-modal table th, .catalog-item-modal table td {
		padding: 6px 15px;
		max-width: 300px;
	}

	.catalog-item-modal table td {
		color: #28217b;
		font-size: 14px;
		font-weight: 600;
	}

	.catalog-item-modal .modal-error-message {
		color: red;
	}

	.catalog-item-modal .container .row {
		margin-bottom: 15px;
	}

	.catalog-item-modal table td input,
	.input_quantity {
		border: 2px solid #28217b;
	    border-radius: 5px;
	    height: 34px;
	    width: 130px;
	    text-align: center;
	    padding: 0px 10px;
	}

	.catalog-item-modal table td input.modal-comment-field {
		width: 235px;
		text-align: left;
	}

	.catalog-item-modal tr.highlighted td {
		color: red;
	}

	.catalog-modal-quantity-save.disabled {
		pointer-events: none;
    	background-color: #c8c8c8;
	}
</style>

<script type="text/javascript">
	$(document).ready(function () {

	$('.catalog-item-amount-modal').click(function () {

		var itemId = parseInt($(this).attr('item-id'));
		if (!itemId || itemId < 1) return;

		actionData = {};

		if (window.asseblyCatalogData && window.asseblyCatalogData[itemId]) 
		{
			actionData = window.asseblyCatalogData[itemId];
		}

		$.ajax({
			url: '<?=$templateFolder?>/ajax.php?itemId=' + itemId + '&modal=1&sessid=' + BX.bitrix_sessid(),
			method: 'POST',
			data: {
				data: actionData,
			},
			dataType: 'json',
			success: function (data) {

				if (data.success && data.content)
				{
					if (data.article) $('#catalog_item_amount_modal .modal-subtitle').html(data.article);

					$('#catalog_item_amount_modal .modal-body').html(data.content);

					if (actionData && actionData.amnt)
					{
						$('.catalog-modal-quantity-save').removeClass('disabled');
						$('.catalog-modal-quantity-save').text('Добавить ' + actionData.amnt);
					}
					else
					{
						$('.catalog-modal-quantity-save').addClass('disabled');
						$('.catalog-modal-quantity-save').text('Обновить');
					}
				}
				else
				{
					$('#catalog_item_amount_modal .modal-body').html('<p class="modal-error-message">'+(data.message ?? 'Ошибка загрузки')+'</p>');
				}
			},
			error: function () {

				$('#catalog_item_amount_modal .modal-body').html('<p class="modal-error-message">Error loading content.</p>');
			},
			complete: function(data) {
				$('#catalog_item_amount_modal').modal('show');
			}
		});
	});

	$(document).on('click body', '.open-catalog-item-child-modal', function() {

		var productId = parseInt($(this).attr('product-id'));
		var storeId = $(this).attr('store-id');
		if (!productId || productId < 1 || !storeId) return;

		$.ajax({
			url: '<?=$templateFolder?>/ajax.php?productId=' + productId + '&storeId=' + storeId + '&modal=2&sessid=' + BX.bitrix_sessid(),
			method: 'GET',
			dataType: 'json',
			success: function (data) {

				if (data.success && data.content)
				{
					if (data.productName) $('#catalog_item_child_modal .modal-subtitle').html(data.productName);

					if (data.productId){
						$('#catalog_item_child_modal [data-entity="main_item_quantity"]').attr('name', `WRITEOFFS[PRODUCT_${productId}][STORE_MAIN]`);
						$('#catalog_item_child_modal [data-entity="main_item_comment"]').attr('name', `COMMENTS[PRODUCT_${productId}][STORE_MAIN]`);
					} 

					$('#catalog_item_child_modal .modal-body').html(data.content);

					
				}
				else
				{
					$('#catalog_item_child_modal .modal-body').html('<p class="modal-error-message">'+(data.message ?? 'Ошибка загрузки')+'</p>');
				}
			},
			error: function () {
				$('#catalog_item_child_modal .modal-body').html('<p>Error loading content.</p>');
			},
			complete: function(data) {
				$('#catalog_item_child_modal').modal('show');
			}
		});
	});

	$(document).on('click body', '.modal-body .product-item-amount-field-btn-minus, .modal-body .product-item-amount-field-btn-plus', function(e) {
		var inp = $(this).parent().find('input');
		if (inp.length > 0 && inp[0]){
			inp = inp[0];
		} else {
			return;
		} 

        if (this.classList.contains('product-item-amount-field-btn-minus')) {
            inp.value = Number(this.parentElement.querySelector('input').value) - 1;
        } else if (this.classList.contains('product-item-amount-field-btn-plus')) {
            inp.value = Number(this.parentElement.querySelector('input').value) + 1;
        }

        if (this.parentElement.querySelector('input').value <= 0){
			$('.product-item-amount-field-btn-minus').addClass("disabled");
			$('.catalog-modal-quantity-save').addClass('disabled');
			inp.value = 0;
			$('.catalog-modal-quantity-save').text('Обновить');
			
		} else {
        	$('.product-item-amount-field-btn-minus').removeClass("disabled");
			$('.catalog-modal-quantity-save').removeClass('disabled');
			$('.catalog-modal-quantity-save').text('Добавить ' + inp.value);
		}
	});

	$(document).on('input', '.modal-body .product-item-amount-field', function() {
	    const $field = $(this);
	    const minValue = parseFloat($field.attr('min-value')) || 0;
	    const currentValue = parseFloat($field.val());

	    if (isNaN(currentValue)) $field.val(minValue);

	    if (currentValue < minValue) {
	        $field.val(minValue);
	        $('.product-item-amount-field-btn-minus').addClass("disabled");
	    }
	    else if (currentValue > minValue)
	    {
	    	$('.product-item-amount-field-btn-minus').removeClass("disabled");
	    }
	    else if (currentValue == minValue)
	    	$('.product-item-amount-field-btn-minus').addClass("disabled");

	    if (currentValue > minValue)
	    {
	    	$('.catalog-modal-quantity-save').text('Добавить ' + (currentValue - minValue));
	    	$('.catalog-modal-quantity-save').removeClass('disabled');
	    }
	    else
	    {
	    	$('.catalog-modal-quantity-save').text('Обновить');
	    	$('.catalog-modal-quantity-save').addClass('disabled');
	    }
	});

	$('.catalog-modal-quantity-save').click(function () {

		$('.sets-table tr').removeClass('highlighted');
		$('.sets-table td.insufficient').text('');

		var data = parseFormData($(this).parent().parent());

		$.ajax({
			url: '<?=$templateFolder?>/ajax.php',
			data: {
				action: 'updateQuantity',
				data: data,
				type: "add",
				sessid: BX.bitrix_sessid()
			},
			method: 'POST',
			dataType: 'json',
			success: function (data) {

				if (data.success)
				{
					BX.UI.Notification.Center.notify({
	                    content: data.message ?? 'Остатки успешно обновлены'
	                });

	                if (data.quantities)
	                {
	                	for (let key in data.quantities) {
							$('#' + key).html(data.quantities[key]);
						}
	                }

	                $('#catalog_item_amount_modal').modal('hide');
				}
				else
				{
					BX.UI.Notification.Center.notify({
	                    content: data.message ?? 'Произошла ошибка',
	                });

	                if (data.itemIds)
	                {
	                	for (let key in data.itemIds) {

							$('.sets-table tr[item-id="'+data.itemIds[key]+'"]').addClass('highlighted');

							if (data.cnts)
								$('.sets-table tr[item-id="'+data.itemIds[key]+'"] td.insufficient').text(data.cnts[data.itemIds[key]]);
						}
	                }
				}
			},
			error: function () {
				BX.UI.Notification.Center.notify({
                    content: 'Request failed!'
                });
			},
			complete: function(data) {
				
			}
		});
	});

	$('.catalog-modal-writeoff-save').click(function () {

		var data = parseFormData($(this).parent().parent());

		var hasValue = false;
		var hasEmptyComment = false;

		$(this).parent().parent().find('input').removeClass('modal-field-error');
		var comments = $(this).parent().parent().find('input.modal-comment-field');

		$(this).parent().parent().find('input.modal-write-off-field').each(function( index, item ) {
			
			if ($(item).val()) {

				hasValue = true;

				var commentInput = comments[index];
				if (commentInput && !$(commentInput).val())
				{
					hasEmptyComment = true;

					$(commentInput).addClass('modal-field-error');
				}
			}
		});

		if (!hasValue)
		{
			BX.UI.Notification.Center.notify({
                content: 'Не проставлено значение к списанию'
            });

            return;
		}

		if (hasEmptyComment)
		{
			BX.UI.Notification.Center.notify({
                content: 'Заполните комментарии к списанию'
            });

            return;
		}

		$.ajax({
			url: '<?=$templateFolder?>/ajax.php',
			data: {
				action: 'writeOff',
				data: data,
				sessid: BX.bitrix_sessid()
			},
			method: 'POST',
			dataType: 'json',
			success: function (data) {

				if (data.success)
				{
					BX.UI.Notification.Center.notify({
	                    content: data.message ?? 'Списание успешно произведенно'
	                });

	                if (data.quantities)
	                {
	                	for (let key in data.quantities) {
							$('#' + key).html(data.quantities[key]);
						}
	                }

	                $('#catalog_item_child_modal').modal('hide');

					location.reload();
	                
				}
				else
				{
					BX.UI.Notification.Center.notify({
	                    content: data.message ?? 'Произошла ошибка',
	                });

	                if (data.productId)
	                {
	                	$('[name="WRITEOFFS[PRODUCT_'+data.productId+'][STORE_MAIN]"]').addClass('modal-field-error');
	                }
				}
			},
			error: function () {
				BX.UI.Notification.Center.notify({
                    content: 'Request failed!'
                });
			},
			complete: function(data) {
				
			}
		});
	});

	function parseFormData($element) {
	    var formData = {};

	    // Find all inputs inside the element
	    $element.find('input').each(function() {

	        var name = $(this).attr('name');
	        var value = $(this).val();

	        // Skip inputs without a name attribute
	        if (!name) return;

	        // Convert names like 'QUANTITY[4][MAIN]' into structured data
	        var nameParts = name.match(/[^[\]]+/g); // Get parts of the name (e.g., ['QUANTITY', '4', 'MAIN'])

	        // Start building the structure
	        var currentLevel = formData;

	        // Iterate over the parts of the name
	        for (var i = 0; i < nameParts.length; i++) {
	            var part = nameParts[i];

	            // If this is the last part, assign the value
	            if (i === nameParts.length - 1) {
	                currentLevel[part] = value;
	            } else {
	                // If the next part is a number, make it an array
	                if (!currentLevel[part]) {
	                    currentLevel[part] = isNaN(nameParts[i + 1]) ? {} : [];
	                }
	                currentLevel = currentLevel[part];  // Move down into the structure
	            }
	        }
	    });

	    return formData;
	}

	$('#catalog_item_amount_modal').on('hidden.bs.modal', function () {
		$('#catalog_item_child_modal').modal('hide');
  	});
});
</script>

<!-- Parent Modal -->
<div class="catalog-item-modal modal fade" id="catalog_item_amount_modal" tabindex="-1" role="dialog">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
		<?if ($buyDisabled === 'N'):?>
			<div class="modal-header">
				<div class="modal-subtitle-block">
					<h5 class="modal-title">Количество остатков</h5>
					<sapn class="modal-subtitle"></sapn>
				</div>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>

			<div class="modal-body">

			</div>

			<div class="modal-footer">
				<button type="button" class="catalog-modal-btn catalog-modal-quantity-save disabled">Обновить</button>
			</div>
			<?else:?>
				<div class="modal-header">
					<h5 style="color: red;padding: 5%;max-width: 30rem;text-align: center;">
						Магазин временно закрыт на инвентаризацию. Приносим извинения за доставленные неудобства и надеемся на ваше понимание.
					</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
			<?endif?>
		</div>
	</div>
</div>

<!-- Child Modal -->
<div class="catalog-item-modal modal fade" id="catalog_item_child_modal" tabindex="-1" role="dialog">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<div class="modal-title modal-title-grid">
					Списание брака
					<sapn style="padding: 6px 15px;font-weight: bold;" class="modal-subtitle"></sapn>
					<table>
						<tr>
							<th>К списанию</th>
							<th>Комментарий</th>
						</tr>
						<tr>
							<td><input class="modal-write-off-field" data-entity="main_item_quantity" name="WRITEOFFS[PRODUCT_1159][STORE_MAIN]" type="text" onkeypress="return event.charCode >= 48 && event.charCode <= 57"></td>
							<td><input class="modal-comment-field" data-entity="main_item_comment" name="COMMENTS[PRODUCT_1159][STORE_MAIN]"></td>
						</tr>
					</table>
				</div>
				
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>

			<div class="modal-body">

			</div>

			<div class="modal-footer">
				<button type="button" class="catalog-modal-btn catalog-modal-writeoff-save">Сохранить</button>
			</div>
		</div>
	</div>
</div>

<!-- component-end -->