<?php
/**
 * Класс   Upsearch
Есть таблица Позиций  -    TablePos     в ней есть много разных полей!
Есть таблица search_full по которой производится поиск данных позиций!
CREATE TABLE IF NOT EXISTS `search_full` (
`inc` int(8) unsigned NOT NULL AUTO_INCREMENT,
`increment` int(8) NOT NULL,
`ratings_order` varchar(6) NOT NULL,
`goroda_id` varchar(30) NOT NULL,
`full_text` text NOT NULL,
PRIMARY KEY (`inc`),
UNIQUE KEY `increment` (`increment`),
FULLTEXT KEY ` full_text ` (`full_text `)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

 Клас должен уметь  обновлять всю таблицу search_full !

Клас должен уметь обновлять последние значения с таблици `TablePos`
 (имя таблици настраимое значение в конфиге)

Полное обновление таблици search_full!
Перед обновленим ОЧИЩАЕМ ВСЮ ТАБЛИЦУ, УБИВАЕМ ИНДЕКС FULLTEXT!

Далее…….
Мы берем с таблици TablePos все записи для обработки и вставки в таблицу search_full!
Берем не все зразу а через итерацию! За несколько раз!
SELECT increment, ratings, goroda,  CONCAT(field1, field2, field3, field,……) as text_
 insert FROM TablePos Order by increment ASC LIMIT 0, 2000
field1, field2, field3, field   - поля которые должны сливатся в одну строчку –
  настраивается в конфиге масивом!
2000 -  шаг итерации (настраиваемое значение в конфиге) с которым должны
 обрабатыватся  записи  (записей может быть и больше 2000 тоесть нужно запускать
 цикл с таким шагом)
Каждый результат выборки нам нужно обработать и вставить в таблицу   search_full!
Поля которые нужно встаить с таблици TablePos в таблицу   search_full  Соответствия
полей:

TablePos                                                search_full
Goroda                                                  goroda_id
Ratings                                                 ratings_order
Increment                                               increment
CONCAT(field1, field2, field3, field,……) as text_insert full_text

 Перед вставкой с таблици  TablePos  в таблицу search_full   поле   text_insert
 нужно обработать функцией!      (учитивать кодировку UTF-8)   используем функции
 которые нормально работают с кодировкой utf-8!
Функция  -
Удаляем теги!
Удаляем мнемоники! Типа &nbsp; ()
заменяет   весь текстовый мусор (все знаки припенания, машынные символы знаки ,
  комы, дуги, точки  и так далее) на пробелы!
Оставляе м цыфровые буквенные пробельные символы!
Далее двойные, тройные пробелы заменяем одним !

Удаляем с этого текста  все слова которые  имеют два и менше симолов))
Деблируем слова с украинскими буквами!
Тесть если в слове  есть    «i» «ї» заменяем на “и”
Тесть если в слове  есть    «є»Є  заменяем на “е”
Но мы такие слова не заменяем а дублируем например
Если встречается слово «рапід»  мы создаем еще одно слово «рапид»
Все делаем мультибайтовыми строуовыми функциями!


--------------------------------------------
Далее вставляем в таблицу search_full   все поля!

После завершения полного обновления снова создаем fulltext индекс для поля full_text
 в таблице  search_full !

Режим реального обновления!
Берем последний increment (SELECT increment  FROM   search_full   order by increment
  DESC limit 1) с таблици search_full  !
Вытягиваем все записи с таблици   TablePos  в которых increment больше за последний
 increment с табицы  search_full  !
И делаем все то же что описано выше только для нових записей с таблици   TablePos!
В режиме реального обноения мы не удаляем индексов и не очищаемм таблицу search_full

Настройки для базы храним в файле!  Вместе с пердидущими настройками

 *
 * PHP version 5
 *
 * @category Upsearch
 * @package  Upsearch
 * @author   DmitrySamoylenko <DmitrySamoylenko@gmail.com>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version  GIT: $Id$
 * @link     https://github.com/s-a--m/Upsearch
 */

/**
 * Upsearch
 *
 * @category Upsearch
 * @package  Upsearch
 * @author   DmitrySamoylenko <DmitrySamoylenko@gmail.com>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version  Release: 0.1
 * @link     https://github.com/s-a--m/Upsearch
 */
class Upsearch
{

    /**
     * count of selected rows
     *
     * @var
     */
    protected $rowsCount;

    /**
     * data to insert
     *
     * @var array
     */
    protected $fieldsData = array();

    /**
     * @var string name of TablePos
     */
    protected $tablePos_name;

    /**
     * Объект mysqli для работы с БД
     *
     * @var mysqli
     */
    protected $db;

    /**
     * Db config for connection array
     *
     * @var array
     */
    private $_dbConf;

    /**
     * errors messages
     *
     * @var array
     */
    protected $message;

    /**
     * count of rows to insert from one table
     *
     * @var
     */
    protected $rowsGetCount;

    /**
     * comma separated list of columns of table TablePos
     *
     * @var
     */
    protected $fieldsList;

    /**
     * offset row to select from
     *
     * @var
     */
    protected $rowsOffset = 0;

    /**
     * construct
     *
     * @param array $settings from config.php
     */
    function __construct
    (
        $settings
    ) {
        $this->tablePos_name = $settings['database']['tablePos_name'];
        $this->search_full_name = $settings['database']['search_full_name'];
        $this->_dbConf = $settings['database']['params'];
        $this->rowsGetCount = $settings['rowsGetCount'];
        $this->fieldsList = $settings['fieldsList'];

    }


    /**
     * Full update from TablePos
     *
     * @return boolean
     * @throws ErrorException
     */
    public function fullUpdate
    ()
    {
        //connect
        try {
            if (!$this->dbConnect()) {
                $m = 'Could not continue without DB connection. ';
                $this->message['all'][] = $m;
                throw new ErrorException($m);
            }
            //connected
            //reset table
            $this->resetTable();

            //get, process
            do {
                $r = $this->getDataTable($this->rowsOffset);
                //process  write data
                if ($this->rowsCount>0) {
                    $this->insertDataTable();
                }
            } While ($r);

        }
        catch (ErrorException $e) {
            //errors handle
            echo 'Errors '. $e->getMessage().'<br/>';
        }
        return true;
    }

    /**
     * resets table search_full and full_text index
     *
     * @throws ErrorException
     *
     * @return boolean
     */
    protected function resetTable
    ()
    {
        //connection should be established
        if (!$this->db) {
            $m = 'resetTable: Trying to reset table without connection to DB. ';
            $this->message['all'][] = $m;
            throw new ErrorException($m);
        }
        //clear table search_full
        $q = 'TRUNCATE TABLE search_full';
        $clear_table = $this->db->query($q);
        if ((!$clear_table) || $this->db->errno) {
            $m = 'resetTable: Errors: '.$this->db->error.' Query was:'
                . $q.'<br/>';;
            $this->message['all'][] = $m;
            throw new ErrorException($m);
        }
        //drop fulltext index
        $q = 'ALTER TABLE '.$this->search_full_name.' DROP INDEX full_text';
        $drop_index = $this->db->query($q);
        if ((!$drop_index) || $this->db->errno) {
            $m = 'resetTable: Errors: '.$this->db->error.' Query was:'
                . $q.'<br/>';;
            $this->message['all'][] = $m;
            //throw new ErrorException($m);
            //drop index return error if not exists
        }
        return true;

    }

    /**
     * get data table tablePos
     *
     * @param int $offset offset row from TablePos
     *
     * @throws ErrorException
     *
     * @return boolean
     */
    protected function getDataTable
    (
        $offset
    ) {
        $this->rowsCount = 0;
        $it_was_last = false;
        //connection should be established
        if (!$this->db) {
            $m = 'GetDataTable: Trying to reset table without connection to DB. ';
            $this->message['all'][] = $m;
            throw new ErrorException($m);
        }
        //get data from TablePos
        $q = 'SELECT increment, ratings, goroda, CONCAT_WS(" ",'
            . $this->fieldsList.
            ') as text_insert FROM '
            . $this->tablePos_name .' ORDER BY increment ASC LIMIT '
            . $offset
            . ', '
            . $this->rowsGetCount;
        $get_data = $this->db->query($q);

        if ((!$get_data) || $this->db->errno) {
            $m = 'GetDataTable: Errors: '.$this->db->error.' Query was:'
                . $q.'<br/>';;
            $this->message['all'][] = $m;
            throw new ErrorException($m);
        }
        $this->rowsCount = $get_data->num_rows;
        if ($this->rowsCount < $this->rowsGetCount) {
            $it_was_last = true;
        }
        $this->rowsOffset += $this->rowsGetCount;

        //fetch data to $fieldsData

        $i=0;//row counter
        while (($row = $get_data->fetch_assoc())!=false) {
            $this->fieldsData[$i]['increment'] = $row['increment'];
            $this->fieldsData[$i]['ratings']= $row['ratings'];
            $this->fieldsData[$i]['goroda'] = $row['goroda'];
            //process data
            $this->fieldsData[$i]['text_insert']
                = $this->filterUTF8String($row['text_insert']);

            $i++;

        }

        return !($this->rowsCount=0 || $it_was_last);

    }

    /**
     * get table tablePos
     *
     * @param int $last_increment $last_increment from search_full
     *
     * @throws ErrorException
     *
     * @return boolean
     */
    protected function getLastDataTable
    (
        $last_increment
    ) {
        $this->rowsCount = 0;
        //connection should be established
        if (!$this->db) {
            $m = 'getLastDataTable: connection to DB. ';
            $this->message['all'][] = $m;
            throw new ErrorException($m);
        }
        //get data from TablePos
        $q = 'SELECT increment, ratings, goroda, CONCAT_WS(" ",'
            . $this->fieldsList.
            ') as text_insert FROM '
            . $this->tablePos_name .' WHERE
            increment >'.$last_increment.'
            ORDER BY increment ASC';
        $get_data = $this->db->query($q);

        if ((!$get_data) || $this->db->errno) {
            $m = 'getLastDataTable: Errors: '.$this->db->error.' Query was:'
                . $q.'<br/>';;
            $this->message['all'][] = $m;
            throw new ErrorException($m);
        }
        $this->rowsCount = $get_data->num_rows;

        //fetch data to $fieldsData

        $i=0;//row counter
        while (($row = $get_data->fetch_assoc())!=false) {
            $this->fieldsData[$i]['increment'] = $row['increment'];
            $this->fieldsData[$i]['ratings']= $row['ratings'];
            $this->fieldsData[$i]['goroda'] = $row['goroda'];
            //process data
            $this->fieldsData[$i]['text_insert']
                = $this->filterUTF8String($row['text_insert']);

            $i++;

        }

        return $this->rowsCount>0;

    }

    /**
     * saves $this->fieldsData data to DB
     *
     * @return boolean
     *
     * @throws ErrorException
     */
    protected function insertDataTable
    ()
    {

        //connection should be established
        if (!$this->db) {
            $m = 'insertDataTable: Trying to insert table without connection to DB.';
            $this->message['all'][] = $m;
            throw new ErrorException($m);
        }
        //create insertion query
        $q = 'INSERT IGNORE INTO '.$this->search_full_name.'
        (increment,ratings_order,goroda_id,full_text) VALUES ';

        for ($j=0;$j<$this->rowsCount;$j++) {
            //row
            $s = "('";
            foreach ($this->fieldsData[$j] as $value) {
                //column
                $s .= $value."','";
            }
            $s = rtrim($s, ",'");
            $s .="'),";

            $q .= $s;

        }
        //INSERT INTO tbl_name (a,b,c) VALUES(1,2,3),(4,5,6),(7,8,9)
        $q = rtrim($q, ',');
        $insertion = $this->db->query($q);
        if ((!$insertion) || $this->db->errno) {
            $m = 'insertDataTable: insertion : '.$this->db->error.' Query was:'
                . $q.'<br/>';
            $this->message['all'][] = $m;
            throw new ErrorException($m);
        }

        $q = 'ALTER TABLE '.$this->search_full_name.' ADD FULLTEXT(full_text)';
        $creation = $this->db->query($q);
        if ((!$creation) || $this->db->errno) {
            $m = 'insertDataTable: alter : Errors: '.$this->db->error.' Query was:'
                . $q.'<br/>';
            $this->message['all'][] = $m;
            throw new ErrorException($m);
        }
        return true;
    }



    /**
     * Удаляем теги!
    Удаляем мнемоники! Типа &nbsp; ()
    заменяет   весь текстовый мусор (все знаки припенания, машынные символы знаки ,
    комы, дуги, точки  и так далее) на пробелы!
    Оставляе м цыфровые буквенные пробельные символы!
    Далее двойные, тройные пробелы заменяем одним !

    Удаляем с этого текста  все слова которые  имеют два и менше симолов))
    Деблируем слова с украинскими буквами!
    Тесть если в слове  есть    «i» «ї» заменяем на “и”
    Тесть если в слове  есть    «є»Є  заменяем на “е”
    Но мы такие слова не заменяем а дублируем например
    Если встречается слово «рапід»  мы создаем еще одно слово «рапид»
    Все делаем мультибайтовыми строуовыми функциями!
     *
     * @param string $str text to process
     *
     * @return string
     */
    protected function filterUTF8String
    (
        $str
    ) {

        if ($str=='') {

            return $str;

        }

        //Удаляем мнемоники! Типа &nbsp; ()
        $patterns[0] = '/&#(\d\d*);/u';
        $patterns[1] = '/&(\w*);/u';
        $patterns[2] = '/[^а-яА-ЯЁёїєЄa-zA-Z0-9]/u';
        //Удаляем с этого текста  все слова которые  имеют два и менше симолов
        $patterns[3] = '/\b[^\s]{1,2}\b/u';

        $str      = preg_replace($patterns, ' ', $str);

        //Деблируем слова с украинскими буквами!

        $str      = preg_replace_callback(
            "/([а-яА-ЯёЁiїєЄ]+)([iїєЄ]+)([а-яА-ЯёЁiїєЄ]+)/u",
            create_function(
                '$matches', '
                    $result = "";
                    switch ($matches[2]) {
                    case "i":
                    case "ї":
                        $result = $matches[1]."и".$matches[3];
                        break;
                    case "є":
                        $result = $matches[1]."е".$matches[3];
                        break;
                    case "Є":
                        $result = $matches[1]."Е".$matches[3];
                        break;
                    }
                    return $matches[1].$matches[2].$matches[3]." ".$result;
            '
            ),
            $str
        );

        $str = preg_replace(
            '/( +)/u',
            ' ',
            $str
        );

        return $str;
    }

    /**
     * Устанавливает соединение с БД
     *
     * требует предварительной установки параметров БД в массив

    ['host']='localhost';
    ['username']=';
    ['password']=
    ['dbname']='';
    ['charset']='utf8';

     *
     * @return bool успешное выполнение
     * @throws ErrorException
     */
    protected function dbConnect
    ()
    {

        $h = $this->_dbConf['host'];
        $u = $this->_dbConf['username'];
        $p = $this->_dbConf['password'];
        $d = $this->_dbConf['dbname'];


        try {
            if (($this->db = new mysqli($h, $u, $p, $d))==false) {
                $m = 'Не удалось подключиться к БД. ';
                $this->message['all'][] = $m;
                throw new ErrorException($m);
            } elseif ($this->db->connect_errno) {
                $m = 'Ошибка подключения к БД: '.$this->db->connect_error;
                $this->message['all'][] = $m;
                throw new ErrorException($m);
            } else {
                $this->db->set_charset($this->_dbConf['charset']);

            }
        }
        catch (ErrorException $e) {
            $m = 'Ошибка подключения: '.$e->getMessage();
            $this->message['all'][] = $m;
            return false;
        }
        return !$this->db->connect_errno;
    }


    /**
     * Режим реального обновления!

    Берем последний increment (SELECT increment  FROM   search_full   order by
    increment
    DESC limit 1) с таблици search_full  !
    Вытягиваем все записи с таблици   TablePos  в которых increment больше за
    последний
    increment с табицы  search_full  !
    И делаем все то же что описано выше только для нових записей с таблици
    TablePos!
    В режиме реального обноения мы не удаляем индексов и не очищаемм таблицу
    search_full
     *
     * @return boolean
     * @throws ErrorException
     *
     */
    public function doRealUpdate
    ()
    {
        //connect
        try {
            if (!$this->dbConnect()) {
                $m = 'Could not continue without DB connection. ';
                $this->message['all'][] = $m;
                throw new ErrorException($m);
            }
            //connected

            ////reset table
            //$this->resetTable();

            //Берем последний increment
            $q = 'SELECT increment FROM '.$this->search_full_name.' order by
                    increment
                    DESC limit 1';
            $selection = $this->db->query($q);
            if ((!$selection) || $this->db->errno) {
                $m = 'doRealUpdate: Errors: '.$this->db->error.' Query was:'
                    . $q.'<br/>';;
                $this->message['all'][] = $m;
                throw new ErrorException($m);
            }
            $res = $selection->fetch_assoc();
            $last_increment = $res['increment'];
            if (!$last_increment) {
                $last_increment = 0;
            }

            //Вытягиваем все записи с таблици   TablePos  в которых increment больше
            //за последний
            //get, process
            do {
                $r = $this->getLastDataTable($last_increment);
                //process  write data
                if ($this->rowsCount>0) {
                    $this->insertDataTable();
                }
            } While ($r);

        }
        catch (ErrorException $e) {
            //errors handle
            echo 'Errors '. $e->getMessage().'<br/>';
        }
        return true;
    }

    /**
     * закрывает соединение с БД
     *
     * @return bool true
     */
    protected function dbClose
    ()
    {
        $this->db->close();
        return true;
    }

}