<html>
<head>
    <!--Import Google Icon Font-->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <!-- Compiled and minified CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0-beta/css/materialize.min.css">

    <!-- Compiled and minified JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0-beta/js/materialize.min.js"></script>

    <script language="JavaScript">

        function check_submit(){
            // tada
        }
    </script>
</head>
<body>
<form class="col s12" method="post" name="generate" id="generate" enctype="multipart/form-data">
    <div class="row">
        <div class="input-field col s12 m1">&nbsp;</div>
        <div class="input-field col s12 m10"><h4><a href="<?php echo $_SERVER['PHP_SELF'];  ?>">Create Database Table from CSV/Excel/ODF File</a></h4></div>
    </div>
    <div class="row">
        <div class="input-field col s12 m1">&nbsp;</div>
        <div class="input-field col s12 m3">
            <input placeholder="Placeholder" id="table_name" name="table_name" value="" type="text" class="validate">
            <label for="table_name">New DB Table Name</label>
        </div>
        <div class="input-field col s12 m2">
            <label>
                <input type="checkbox" id="also_insert_data" name="also_insert_data" checked="checked" />
                <span>Also Insert Data</span>
            </label>
        </div>
        <div class="input-field col s12 m3">
            <div class="file-field input-field">
                <div class="btn">
                    <span>Pick a File</span>
                    <input type="file" name="source_file" id="source_file">
                </div>
                <div class="file-path-wrapper">
                    <input class="file-path validate" type="text">
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="input-field col s12 m1">&nbsp;</div>
        <div class="input-field col s12 m3">
            <input placeholder="Placeholder" id="hostname" name="hostname" value="localhost" type="text" class="validate">
            <label for="hostname">DB Hostname</label>
        </div>
        <div class="input-field col s12 m2">
            <input placeholder="Placeholder" id="db_name" name="db_name" value="" type="text" class="validate">
            <label for="db_name">DB Name</label>
        </div>

        <div class="input-field col s12 m2">
            <input placeholder="Placeholder" id="login" name="login" value="root" type="text" class="validate">
            <label for="login">DB Login</label>
        </div>
        <div class="input-field col s12 m2">
            <input placeholder="Placeholder" id="password" name="password" value="" type="text" class="validate">
            <label for="password">DB Password</label>
        </div>
        <div class="input-field col s12 m2">
            <button class="btn waves-effect waves-light" type="submit" onclick="check_submit()" name="action">
                <i class="material-icons right">send</i>Submit
            </button>
        </div>
    </div>

</form>
<?php

require 'vendor/autoload.php';

session_start();



/************ FILE SUBMITTED ********************/
if( isset($_FILES['source_file']) ){


    // DB SHIT Connect & Execute
    $mysqli = new mysqli($_POST['hostname'], $_POST['login'], $_POST['password'], $_POST['db_name']);
    if ($mysqli->connect_errno) {
        echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
        die("<hr>No Connection.");
    }



    $inputFileName = $_FILES['source_file']['tmp_name'];

    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($inputFileName);
    $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

//    print_r($sheetData);

    $sql="CREATE TABLE `".$_POST['table_name']."` ( `id` int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT, ";

    $sql_insert ="INSERT INTO `".$_POST['table_name']."` (";
  

    // loop first line
    foreach($sheetData[1] AS $idx => $value){
        $db_field_name = str_replace(array(" ","-"),"_",strtolower($value));

        // small fix for ID name same as auto id
        if($db_field_name=='id'){
            $db_field_name='in_file_id';
        }
        if(!empty($db_field_name) ){
            $sql.="\n `$db_field_name` varchar(255) COLLATE utf8_unicode_ci NULL, ";
            $last_idx = $idx;
            $sql_insert.=" $db_field_name,";
        }


    }


    $sql_insert  = substr( $sql_insert,0, -1).") VALUES ( ";
    $sql = substr($sql,0,-2)." ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC";

    echo "<pre>".$sql."</pre>";

    // create table RUN
    if (!$mysqli->query($sql)) {
        echo "Table creation failed: (" . $mysqli->errno . ") " . $mysqli->error;
        die("\n<hr>Well Shit....");
    }else{
        // $mysqli->query("ALTER TABLE `".$_POST['table_name']."` ADD PRIMARY KEY(`id`)");
    }



    if(isset($_POST['also_insert_data'])){
        // only if we want to insert data


        for($i=2;$i<=sizeof($sheetData);$i++){
            // list all data lines

            $stop=false;
            $sql_insert_one='';

            foreach($sheetData[$i] AS $idx => $value){

                if( !$stop ){
                    $sql_insert_one .=" '".str_replace("'","''",$value)."', ";
                    if($idx==$last_idx){ $stop=true; }
                }

            }
            $full_insert = $sql_insert . substr( $sql_insert_one,0, -2).")";
            if (!$mysqli->query($full_insert)) {
                echo "Data Insert Failed: (" . $mysqli->errno . ") " . $mysqli->error;
                die("\n<hr>Well Shit....\n[ $full_insert ]");
            }
        }
    }






}

?>
</body>
</html>
