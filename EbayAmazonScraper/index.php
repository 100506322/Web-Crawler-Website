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
    echo "Connection failed: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Web Crawler Website for eBay and Amazon</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Web crawler website that crawls both Amazon and eBay for a product.">
    <style>

        h2 {
            font-family: Helvetica, serif;
        }

        body {
            margin: 0;

        }
        .header {
            background-color: #4472C4;
            padding: 20px;
            text-align: center;
            font-family: Helvetica, serif;
        }

        table {
            table-layout: fixed;
        }
        #price {
            cursor: pointer;
        }
        #table {
            font-family: Arial, Helvetica, sans-serif;
            border-collapse: collapse;
            width: 100%;
            overflow: hidden;
        }

        #table td, #table th {
            border: 1px solid #ddd;
            padding: 8px;
            overflow: hidden;
        }

        #table tr.ebay:nth-child(even){background-color: lightskyblue;}
        #table tr.ebay:nth-child(odd){background-color: lightblue;}
        #table tr.amazon:nth-child(even){background-color: #FF9900;}
        #table tr.amazon:nth-child(odd){background-color: coral;}

        #table tr:hover {background-color: grey !important;}

        #table th {
            padding-top: 12px;
            padding-bottom: 12px;
            text-align: left;
            background-color: #04AA6D;
            color: white;
            overflow: hidden;
        }
        .button {
            padding: 9px;
            background-color: #04AA6D;
            color: white;
            border-style: none;
            cursor: pointer;
        }
        tr {
            cursor: pointer;
        }
    </style>


</head>
<body>
<div class="header">

    <h1>Amazon and eBay Web Crawler</h1>

    <form method="POST" action="" accept-charset="utf-8">
        <div id="Wrapper" style="padding-right: 10%">

            <input name="Products" placeholder="Search eBay and Amazon for products.." style=" padding: 8px; width: 250px"/>
            <input class="button" type="submit" name="submit" value="Submit me!"  />

            <div class="InputBox" style="float: left">
                <input id="myInput" onkeyup="SearchFunction()" placeholder="Search table for products.." style=" padding: 8px" type = "text">
            </div>
        </div>

    </form>

</div>

<table id="table" class="Rows">
    <thead>

    <tr>
        <th class="tableheading">Product Title</th>
        <th class="tableheading">Link</th>
        <th class="tableheading">Reviews</th>
        <th class="tableheading" id="price" onclick="sortTable()">Price</th>


    </tr>
    </thead>
    <tbody>
    <?php
    //Checks if user input, Products, is empty or not
    if (!empty($_POST['Products']))
    {
        $keyword = str_replace(" ", "+", $_POST['Products']);
        //Executing a python script while passing the user input as keyword and saving the output of python
        //file as $ebay_data
        $ebay_data = exec('python main.py '.$keyword.'');

        //Decoding the returned json from the python script into objects that we can call
        $ebay_decode_data = json_decode($ebay_data);

        $i = 0;
        $d = 0;

        //Loop through.

        while ($i < count($ebay_decode_data))
        {
            if ($ebay_decode_data[$i]->item->title != "Shop on eBay") {
                $ebay_decode_data[$i]->item->title = str_replace('"', "", $ebay_decode_data[$i]->item->title);
                $ebay_decode_data[$i]->item->title = str_replace("'", "", $ebay_decode_data[$i]->item->title);
                $ebay_decode_data[$i]->item->title = str_replace("+", "", $ebay_decode_data[$i]->item->title);
                $ebay_decode_data[$i]->item->title = str_replace("&", "", $ebay_decode_data[$i]->item->title);
                //Output table adding the scraped data from python
                echo "<tr class='ebay' onclick=\"window.location='PriceHistoryGraph.php?ProductTitle=".$ebay_decode_data[$i]->item->title."'\">";
                //Product Title
                echo "<td>" . $ebay_decode_data[$i]->item->title . "</td>";
                //Link
                if (strcmp($ebay_decode_data[$i]->item->link, "URL Not Available") == 0) {
                    echo "No URL";
                } else {
                    echo "<td><a href='" . $ebay_decode_data[$i]->item->link . "'>&nbsp;" . $ebay_decode_data[$i]->item->link . "</a></td>";
                }
                //Reviews
                echo "<td style='text-align: center'>" . $ebay_decode_data[$i]->reviews . "</td>";
                //Price
                echo "<td class='price'>" . $ebay_decode_data[$i]->item->price . "</td>";

                echo "</tr>";

                $price = str_replace("£", "", $ebay_decode_data[$i]->item->price);
                if (strpos($price, "to") !== false) {
                    $price = substr($price, 0, strpos($price, "to"));
                }
                $price = floatval($price);

                try {
                    //Add product to database
                    $data = $conn->exec("INSERT INTO product (Title, Retailer) VALUES ('" . $ebay_decode_data[$i]->item->title . "','eBay'); 
                    INSERT INTO price (PriceDate, Price, ProductID) VALUES ('" . date("Y-m-d") . "', " . $price . ", LAST_INSERT_ID());");
                }
                catch(Exception $e) {
                    //If above fails, only insert price.
                    if ($e->errorInfo[1] == 1062) {
                        try {
                            $data = $conn->exec("INSERT INTO price (PriceDate, Price, ProductID) 
                            VALUES ('" . date("Y-m-d") . "', " . $price . ", (SELECT ProductID FROM product WHERE TITLE = '".$ebay_decode_data[$i]->item->title."'))");
                        }
                        catch (Exception $e) {
                            # If same date and price, must be duplicate from website, dont save.
                            if ($e->errorInfo[1] == 1062 or $e->errorInfo[1] == 1242) {
                                #Delete entry to avoid continuous loop
                                array_splice($ebay_decode_data,$i ,1);
                                $i++;
                                continue;
                            }
                            else {
                                echo 'ExceptionL202 - > ';
                                var_dump($e->getMessage());
                            }
                        }
                    }
                    else {
                        echo 'ExceptionL208 - > ';
                        var_dump($e->getMessage());

                    }

                }

            }
            else {
                $i++;
                continue;
            }
            $i++;
        }

        //Execute another python file for amazon scrape as its different
        $amazon_data = exec('python amazon2.py '.$keyword.'');
        $amazon_decode_data = json_decode($amazon_data);
        $i = 0;

        while ($i < count($amazon_decode_data))
        {
            $amazon_decode_data[$i]->Product->Title = str_replace('"', "", $amazon_decode_data[$i]->Product->Title);
            $amazon_decode_data[$i]->Product->Title = str_replace("'", "", $amazon_decode_data[$i]->Product->Title);
            $amazon_decode_data[$i]->Product->Title = str_replace("+", "", $amazon_decode_data[$i]->Product->Title);
            $amazon_decode_data[$i]->Product->Title = str_replace("&", "", $amazon_decode_data[$i]->Product->Title);
            echo "<tr class='amazon' onclick=\"window.location='PriceHistoryGraph.php?ProductTitle=".$amazon_decode_data[$i]->Product->Title."'\">";
            //Product Title
            echo "<td>".$amazon_decode_data[$i]->Product->Title."</td>";
            //Link
            echo "<td><a href='".$amazon_decode_data[$i]->Product->Link."'>&nbsp;".$amazon_decode_data[$i]->Product->Link."</a></td>";
            //Reviews
            echo "<td style='text-align: center'>".$amazon_decode_data[$i]->Product->NumberofProductReviews."</td>";
            //Price
            echo "<td class='price'>".$amazon_decode_data[$i]->Product->Price."</td>";

            echo "</tr>";

            $price = str_replace("£", "", $amazon_decode_data[$i]->Product->Price);

            $price = floatval($price);

            try {
                $data = $conn->exec("BEGIN; INSERT INTO product (Title, Retailer) VALUES ('".$amazon_decode_data[$i]->Product->Title."','Amazon'); INSERT INTO price (PriceDate, Price, ProductID) VALUES ('".date("Y-m-d")."', ".$price.",LAST_INSERT_ID());COMMIT; ");
                $i++;
            }
            catch (Exception $e) {
                if ($e->errorInfo[1] == 1062) {
                    # If Duplicate Entry
                    try {
                        $data = $conn->exec("INSERT INTO price (PriceDate, Price, ProductID) VALUES ('" . date("Y-m-d") . "', " . $price . ", (SELECT ProductID FROM product WHERE TITLE = '".$amazon_decode_data[$i]->Product->Title."'))");
                        $i++;
                    }
                    catch (Exception $e) {
                        if ($e->errorInfo[1] == 1062 or $e->errorInfo[1] == 1242) {
                            array_splice($amazon_decode_data,$i ,1);
                            continue;
                        }
                        else {
                            echo 'ExceptionL300 - > ';
                            var_dump($e->getMessage());
                            $i++;
                        }
                    }
                }
                else {
                    echo 'ExceptionL307 - > ';
                    var_dump($e->getMessage());
                    $i++;
                }
            }
        }

    }
    else
    {
        //Tells user to enter keyword if user input is empty
        echo "<h2 style='text-align: center'>Enter a keyword to search</h2>";
    }

    echo "</tbody>";
    echo "</table>";
    ?>
    </tbody>
    <script>

        //Searches first column in table
        function SearchFunction() {
            var input, filter, table, tr, td, i, txtValue;
            input = document.getElementById("myInput");
            filter = input.value.toUpperCase();
            table = document.getElementById("table");
            tr = table.getElementsByTagName("tr");
            for (i = 0; i < tr.length; i++) {
                td = tr[i].getElementsByTagName("td")[0];
                if (td) {
                    txtValue = td.textContent || td.innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }
            }
        }
        function sortTable(n) {
            var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
            table = document.getElementById("table");
            switching = true;

            dir = "asc";

            while (switching) {

                switching = false;
                rows = table.getElementsByTagName("TR");

                for (i = 1; i < (rows.length - 1); i++) {

                    shouldSwitch = false;

                    x = rows[i].getElementsByTagName("TD")[3];
                    y = rows[i + 1].getElementsByTagName("TD")[3];
                    xText = parseFloat(x.innerHTML.split('£')[1].replace(/,/g, ''));
                    yText = parseFloat(y.innerHTML.split('£')[1].replace(/,/g, ''));

                    if (dir === "asc") {
                        if (xText > yText) {
                            shouldSwitch = true;
                            break;
                        }
                    } else if (dir === "desc") {
                        if (xText < yText) {
                            shouldSwitch = true;
                            break;
                        }
                    }
                }
                if (shouldSwitch) {

                    rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
                    switching = true;

                    switchcount++;
                } else {

                    if (switchcount === 0 && dir === "asc") {
                        dir = "desc";
                        switching = true;
                    }
                }
            }
        }
        $("tr").click(function(){
            window.location = "PriceHistoryGraph.php";
        });
    </script>
</body>
</html>
