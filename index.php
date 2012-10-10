<?php
header('Content-Type:text/html;charset=utf-8');
ini_set("display_errors", 1);
ini_set("track_errors", 1);
ini_set("html_errors", 1);
error_reporting(E_ALL);

echo 'TEST<br/>';

function filterUTF8String
(
    $str
) {

    //Удаляем мнемоники! Типа &nbsp; ()
    $patterns[0] = '/&#(\d\d*);/u';
    $patterns[1] = '/&(\w*);/u';
    $patterns[2] = '/[^а-яА-ЯЁёїєЄa-zA-Z0-9]/u';
    //Удаляем с этого текста  все слова которые  имеют два и менше симолов
    $patterns[3] = '/\b[^\s]{1,2}\b/u';

    $str      = preg_replace($patterns, ' ', $str);

    /**
     * callback function for preg_replace_callback
     *
     * @param array $matches preg match array of string matches
     *
     * @return string
     */
    function callback
    (
        $matches
    ) {

        $result = '';
        switch ($matches[2]) {
        case 'i':
        case 'ї':
            $result = $matches[1].'и'.$matches[3];
            break;
        case 'є':
            $result = $matches[1].'е'.$matches[3];
            break;
        case 'Є':
            $result = $matches[1].'Е'.$matches[3];
            break;
        }
        return $matches[1].$matches[2].$matches[3].' '.$result;
    }
    //Деблируем слова с украинскими буквами!
    $str      = preg_replace_callback(
        "/([а-яА-ЯёЁiїєЄ]+)([iїєЄ]+)([а-яА-ЯёЁiїєЄ]+)/u",
        'callback',
        $str
    );

    $str = preg_replace('/( +)/u', ' ', $str);

    return $str;
}

$s= filterUTF8String('askgjert kjqbnw!@#%erjktbq кiев москва &nbsp;');
echo $s;