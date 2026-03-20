<?php
$new_password = 'Deadlife123@'; // <-- замените на свой желаемый пароль
$hash = password_hash($new_password, PASSWORD_DEFAULT);

echo "Password: " . $new_password . "<br>";
echo "Hash: " . $hash . "<br>";
echo "<br>SQL-запрос для phpMyAdmin:<br>";
echo "<code>UPDATE admins SET password_hash = '" . $hash . "' WHERE id = 1;</code>";
?>