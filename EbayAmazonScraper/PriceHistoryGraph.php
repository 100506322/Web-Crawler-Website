<?php
$servername = "127.0.0.1";
$username = "root";
$password = "";
$db = "pricehistory";
try {
    $conn = new PDO("mysql:host=$servername;dbname=pricehistory", $username, $password);
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
}
catch(PDOException $e)
{
    #echo "Connection failed: " . $e->getMessage();
}
$data = $conn->prepare("SELECT ProductID FROM product WHERE Title = '".$_GET['ProductTitle']."'");
$data->execute();
$response = array();

while ($OutputData = $data->fetch(PDO::FETCH_ASSOC)) {
    $response[] = $OutputData;
}
if (isset($response[0]["ProductID"])) {
    $data1 = $conn->prepare("SELECT Price, PriceDate FROM price WHERE ProductID = ".$response[0]["ProductID"]." and PriceDate BETWEEN '2022-02-18' AND '2022-03-07';");
    $data1->execute();
    $response1 = array();
    while ($OutputData1 = $data1->fetch(PDO::FETCH_ASSOC)) {
        $PriceDate[] = $OutputData1["PriceDate"];
        $Price[] = $OutputData1["Price"];
    }
}
else {
    echo "No Price Data for this product in the database";
    print_r($response);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <style>
        .header {
            background-color: #4472C4;
            padding: 20px;
            text-align: center;
            font-family: Helvetica, serif;
        }
        body {
            margin: 0;
        }
        h1 {
            font-size: x-large;
        }
    </style>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Graph</title>
</head>
<div class="header">
    <h1 style="text-align: center">Price History from the last 30 Days for:</h1>
    <h1><?php echo $_GET['ProductTitle'];?></h1>

</div>
<body>

<div style="width: 80%; :center; margin-top: 10px">
    <canvas  id="chartjs_bar"></canvas>
</div>
</body>
<script src="//code.jquery.com/jquery-1.9.1.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/Chart.js/2.4.0/Chart.min.js"></script>
<script type="text/javascript">
    var ctx = document.getElementById("chartjs_bar").getContext('2d');
    var myChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels:<?php echo json_encode($PriceDate); ?>,
            datasets: [{
                backgroundColor: [
                    "#83d555",
                    "#017596",
                    "#fed167",
                    "#97fac8",
                    "#e7060b",
                    "#5400ad",
                    "#8c3965",
                    "#deaf19",
                    "#e8c204",
                    "#27fb9e",
                    "#eefd27",
                    "#aeb5d0",
                    "#b1cdbc",
                    "#e1305b",
                    "#d02cbf",
                    "#13e0dd",
                    "#900e84",
                    "#de4c9a"
                ],
                data:<?php echo json_encode($Price); ?>,
            }]
        },
        options: {
            legend: {
                display: false,
                position: 'bottom',

                labels: {
                    fontColor: '#71748d',
                    fontFamily: 'Circular Std Book',
                    fontSize: 14,
                }
            },
            scales: {
                yAxes: [{
                    display: true,
                    ticks: {
                        suggestedMin: 0,
                        beginAtZero: true
                    }
                }]
            }
        }
    });
</script>
</html>