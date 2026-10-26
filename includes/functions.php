<?php
// user-defined functions used by the pages of the site
// include it after config.php: include('../includes/functions.php');

// checks the email format, e.g. juan@gmail.com
function isValidEmail($email)
{
    return preg_match("/^[\w.+-]+@\w+(\.\w+)+$/", $email);
}

// checks if an uploaded file is a JPG or PNG, $type is $_FILES['...']['type']
function isImage($type)
{
    return $type == "image/jpeg" || $type == "image/jpg" || $type == "image/png";
}

// saves an uploaded photo in the images folder and returns its path, e.g. images/1730000000_photo.jpg
// $folder is where that images folder is, e.g. '../../user/' (leave it empty for the current folder)
function savePhoto($source, $fileName, $folder = '')
{
    $target = 'images/' . time() . '_' . $fileName;
    move_uploaded_file($source, $folder . $target) or die("Couldn't copy");
    return $target;
}

// hides bad words with a regular expression, e.g. "shit" becomes "****"
// \b = whole words only, so "class" or "assessment" are not changed; i = any capital letters
function censor($text)
{
    $badWords = array('fuck', 'shit', 'bitch', 'asshole', 'bastard', 'damn', 'putangina', 'tangina', 'gago', 'tanga', 'bobo', 'ulol', 'tarantado', 'punyeta');
    foreach ($badWords as $word) {
        $text = preg_replace("/\b{$word}\w*\b/i", "****", $text);
    }
    return $text;
}

// shows an amount in pesos, e.g. peso(1500) gives ₱ 1,500.00
function peso($amount)
{
    return "&#8369; " . number_format($amount, 2);
}
