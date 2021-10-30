<?php

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

/**
 * Configure Database connection credentials
 */
$servername = "localhost";
$username = "";
$password = "";
$dbname = "";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

getUsers($servername, $username, $password, $dbname, $conn);

/**
 * Get Users
 *
 * @param string] $servername
 * @param string] $username
 * @param string] $password
 * @param string] $dbname
 * @param $conn
 * @return void
 */
function getUsers($servername, $username, $password, $dbname, $conn)
{
    if($servername . $username .$password . $dbname == "")
    {
        return false;
    }
    //Get the page size and page number from the Datatables from client side
    $offset = !empty($_GET["start"]) ? filter_input(INPUT_GET, "start") : 0;
    $limit = !empty($_GET["length"]) ? filter_input(INPUT_GET, "length") : 10;
    //Get the search term
    $search = !empty($_GET["search"]) ? $_GET["search"] : array();
    $searchTerm = !empty($search["value"]) ? htmlspecialchars($search["value"]) : "";
    //Set the table name
    $table = "guests";
    //Prepare MySQL query to get data from table
    $query = "SELECT `id`, `first_name`, `last_name`, `country` FROM $table";
    if (!empty($searchTerm)) {
        $query .= " WHERE first_name LIKE '%" . $searchTerm . "%' OR last_name LIKE '%" . $searchTerm . "%'";
    }
    $query .= " LIMIT $limit OFFSET $offset";
    try {
        //Execute query an save result set in $results
        $result = $conn->query($query);
        $users = array();
        if ($result->num_rows > 0) {
            //Loop through the result set
            while ($row = $result->fetch_assoc()) {
                //Prepare the data in $users
                array_push($users, [
                    "id" => $row["id"],
                    "firstname" => $row["first_name"],
                    "lastname" => $row["last_name"],
                    "country" => $row["country"]
                ]);
            }
        }
        //Get total rows present in the table in $recordsTotal
        $recordsTotal = $conn->query("SELECT count(*) FROM $table")->fetch_row();

        //Get the total rows present in the table after applying filters in $recordsFiltered
        $countFiltered = "SELECT count(*) FROM $table";
        if (!empty($searchTerm)) {
            $countFiltered .= " WHERE first_name LIKE '%" . $searchTerm . "%' OR last_name LIKE '%" . $searchTerm . "%'";
        }
        $recordsFiltered = $conn->query($countFiltered)->fetch_row();

    } catch (\Throwable $th) {
        throw $th;
    }
    finally {
        //Close the connection after MySQL work is done
        $conn->close();
    }
    
    //Prepare data as required by the Datatables plugin.
    $data = [
        "draw" => !empty($_GET["draw"]) ? filter_input(INPUT_GET, "draw") : 0,
        "recordsTotal" => $recordsTotal[0],
        "recordsFiltered" => $recordsFiltered[0],
        "data" => $users
    ];

    //Send the data to client side in JSON format.
    echo json_encode($data);
    exit;
}

?>