<?php 
require 'functions.php';
require 'dataBase.php'; 

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="../css/button.css">
</head>
<body>
<h1 class="h1">To-Do List App<h1>
    <div class="workspace">
    <?php setNewWork(); ?>
    <div class="info">
        <?php showItems();?>   
    </div>
    </div>
    <div class="block">
    <?php showDbButtons();?>
    </div>

</body>
</html>
