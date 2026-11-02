<?php
// FUNCTION: checks the email format with a regular expression, e.g. juan@gmail.com
function isValidEmail($email)
{
    return preg_match("/^[\w.+-]+@\w+(\.\w+)+$/", $email);
}

// FUNCTION: returns true only if the uploaded file is a JPG or PNG
function isImage($type)
{
    return $type == "image/jpeg" || $type == "image/jpg" || $type == "image/png";
}

// FUNCTION: saves an uploaded photo in the images folder and returns its path
// (spaces, quotes and other symbols in the file name become _ so the <img> link never breaks)
function savePhoto($source, $fileName, $folder = '')
{
    $fileName = preg_replace("/[^A-Za-z0-9._-]/", "_", $fileName);
    $target = 'images/' . time() . '_' . $fileName;
    move_uploaded_file($source, $folder . $target) or die("Couldn't copy");
    return $target;
}

// FUNCTION: replaces bad words with **** using a regular expression
function censor($text)
{
    $badWords = array('fuck', 'shit', 'bitch', 'asshole', 'bastard', 'damn', 'putangina', 'tangina', 'gago', 'tanga', 'bobo', 'ulol', 'tarantado', 'punyeta');
    foreach ($badWords as $word) {
        $text = preg_replace("/\b{$word}\w*\b/i", "****", $text);
    }
    return $text;
}

// FUNCTION: formats an amount in pesos, e.g. peso(1500) gives ₱ 1,500.00
function peso($amount)
{
    return "&#8369; " . number_format($amount, 2);
}
