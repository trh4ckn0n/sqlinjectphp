<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>SQL Injection Tool by Trhacknon</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <h1>SQL Injection Tool - by Trhacknon</h1>
  <form method="post" action="index.php">
    <label for="type">Type d'injection :</label>
    <select name="type" id="type">
      <option value="1">1 : the most popular</option>
      <option value="2">2 : popular</option>
      <option value="3">3 : less popular</option>
      <option value="4">4 : rare</option>
    </select>
    <br><br>

    <label for="url">URL cible :</label><br>
    <input type="text" name="url" id="url" placeholder="http://site.com/vuln.php?id=1" size="80">
    <br><br>

    <input type="submit" value="Lancer l'injection">
  </form>

  <pre>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'];
    $url = $_POST['url'];
    if (!empty($url)) {
        include('core.php');
    } else {
        echo "Veuillez spécifier une URL.";
    }
}
?>
  </pre>
</body>
</html>
