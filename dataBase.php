<?php
class DataBase{
    private $host = "mysql:host=localhost";   //<--------введите в эти поля данные своего сервера и БД
    private $user = 'root';
    private $password = '';
    function createDB($name){
        try{
            $conn = new PDO($this->host, $this->user, $this->password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $mysql = 'CREATE DATABASE '.$name; 
            $conn->exec($mysql);
            echo '<p>База данных "ToDoList" успешно создна!</p>';
            $this->createTable();
        } catch (PDOException $e){
            //echo '<p>База данных "ToDoList" уже существует.</p>';
        }
    }
    function dropDB($name){
        try{
            $conn = new PDO($this->host, $this->user, $this->password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $mysql = 'DROP DATABASE '.$name;
            $conn->exec($mysql);
            echo '<p>База данных "ToDoList" успешно удалена!</p>';
        } catch (PDOException $e){
          //  echo '<p>"ToDoList" нет в базе данных.</p>';
        }
    }
    function createTable(){
        try{
        $conn = new PDO($this->host.';dbname=toDoList', $this->user, $this->password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $mysql = 'CREATE TABLE plans (
            id INTEGER AUTO_INCREMENT PRIMARY KEY , 
            title VARCHAR(30), 
            plansDate DATE, 
            plansDescription VARCHAR(60), 
            plansStatus VARCHAR(10))';
         $conn->exec($mysql); 
        } catch (PDOException $e){
           // echo $e->getMessage();
        }
        
    }
    function addItem($title, $data, $description){
        if($title || $data || $description){
        try{
        $conn = new PDO($this->host.';dbname=toDoList', $this->user, $this->password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $mysql = "INSERT INTO Plans(title, plansDate, plansDescription, plansStatus) VALUES('".$title."', '".$data."', '".$description."', 'planned');";
        $conn->exec($mysql);
        } catch(PDOException $e){
           // echo $e->getMessage();
        }
    }
    }
    function getS(){
        try {
            $conn = new PDO($this->host.';dbname=toDoList', $this->user, $this->password);
            $sql = "SELECT * FROM Plans";
            $result = $conn->query($sql);
            echo '<table class="table"><tr align="left"><th>Дата</th><th>Планы</th><th>Статус</th><th></th></tr>';
            foreach($result as $row){
                echo '<tr>';
                    echo '<td class="tbl1">' . $row["plansDate"] . '</td>';
                    echo '<td class="tbl2" title="'.$row["plansDescription"].'">' . $row["title"] . '</td>';
                    echo '<td class="tbl3">' . $row["plansStatus"] . '</td>';
                    echo '<td><form action="delete.php" method="post">
                                <input type="hidden" name="id" value="' . $row['id'] . '" />
                                <input class="tbl4" type="submit" value="Delete">
                            </form></td>';
                echo "</tr>";
            }
            echo "</table>";
        }
        catch (PDOException $e) {
            //echo "Database error: " . $e->getMessage();
        }
    }
}
?>