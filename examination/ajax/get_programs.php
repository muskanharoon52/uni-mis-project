<?php

include("../../config/db_connect.php");

$query = "SELECT program_id, program_name
          FROM programs
          ORDER BY program_name ASC";

$result = mysqli_query($conn,$query);

echo '<option value="">Select Program</option>';

while($row=mysqli_fetch_assoc($result))
{
    echo '<option value="'.$row['program_id'].'">';
    echo $row['program_name'];
    echo '</option>';
}

?>