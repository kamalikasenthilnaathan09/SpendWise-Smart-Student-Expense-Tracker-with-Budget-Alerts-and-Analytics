<?php

include 'db.php';

$title = $_POST['title'];
$amount = $_POST['amount'];
$category = $_POST['category'];
$expense_date = $_POST['expense_date'];

$sql = "INSERT INTO expenses(title, amount, category, expense_date)

VALUES('$title','$amount','$category','$expense_date')";

if($conn->query($sql)==TRUE){
    header("Location: http://localhost:8080/SpendWise/dashboard.php");
}
else{

    echo "Error: ".$conn->error;

}

?>