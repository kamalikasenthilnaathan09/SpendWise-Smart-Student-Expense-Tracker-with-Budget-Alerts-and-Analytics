<?php include("db.php"); ?>

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