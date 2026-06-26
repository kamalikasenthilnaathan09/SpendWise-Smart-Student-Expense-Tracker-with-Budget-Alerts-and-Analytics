<?php include 'db.php'; ?>

<!DOCTYPE html>
<html>

<head>

<title>Expense Analytics</title>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>

<body>

<h2>Expense Analytics</h2>

<canvas id="expenseChart" width="400" height="400"></canvas>

<?php

$data = [];

$result = $conn->query("SELECT category, SUM(amount) as total
                        FROM expenses
                        GROUP BY category");

while($row = $result->fetch_assoc()){

    $data[] = $row;

}

?>

<script>

const labels = [

<?php
foreach($data as $d){

    echo "'".$d['category']."',";

}
?>

];

const values = [

<?php
foreach($data as $d){

    echo $d['total'].",";

}
?>

];

new Chart(document.getElementById('expenseChart'), {

    type: 'pie',

    data: {

        labels: labels,

        datasets: [{

            label: 'Expenses',

            data: values

        }]
    }

});

</script>

</body>
</html><?php include("db.php"); ?>

<!DOCTYPE html>
<html>
<head>
    <title>Print Expenses</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body class="container mt-5">

<h2>Expense Report</h2>

<button onclick="window.print()" class="btn btn-primary mb-3">
    Print Report
</button>

<table class="table table-bordered">

<tr>
    <th>Title</th>
    <th>Amount</th>
    <th>Category</th>
    <th>Date</th>
</tr>

<?php

$result = mysqli_query($conn,"SELECT * FROM expenses");

while($row = mysqli_fetch_assoc($result)){

echo "<tr>
<td>{$row['title']}</td>
<td>₹{$row['amount']}</td>
<td>{$row['category']}</td>
<td>{$row['expense_date']}</td>
</tr>";

}

?>

</table>

</body>
</html>