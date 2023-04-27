<?php
function setNewWork(){
    print<<<_HTML_
    <form action="index.php" method="post">
    <input class="insert" type="text" name="title" placeholder="Добавить действие">
    <input class="date" type="date" name="date">
    <textarea  name="description" placeholder="Подробное описание работы"></textarea>
    <button  type="submit" name="button">Add</button>
    </form>
    _HTML_;
    if(isset($_POST['button'])){
        $db = new DataBase;
        $db->addItem($_POST['title'],$_POST['date'],$_POST['description']);
    }
}
function showDbButtons(){

    print<<<_HTML_
    <form action="index.php" method="post">
    <button name="button1">Create ToDoList DB</button>
    <button name="button2">Delete ToDoList DB</button>
    </form>
    _HTML_;
    if(isset($_POST['button1'])){
        $db = new DataBase();
        $db->createDB('toDoList');
    }   
    if(isset($_POST['button2'])){
        $db = new DataBase();
        $db->dropDB('toDoList');
    }

}
function showItems(){
    $db = new DataBase();
    $db->getS();
}
?>

