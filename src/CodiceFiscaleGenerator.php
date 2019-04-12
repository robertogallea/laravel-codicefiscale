<?php

namespace robertogallea\LaravelCodiceFiscale;

use Carbon\Carbon;
use robertogallea\LaravelCodiceFiscale\Exceptions\CodiceFiscaleException;

class CodiceFiscaleGenerator
{
    protected $_formatoData = 'd-m-Y';

    protected $_parametri = [];

    protected $_consonanti = [
        'B', 'C', 'D', 'F', 'G', 'H', 'J', 'K',
        'L', 'M', 'N', 'P', 'Q', 'R', 'S', 'T',
        'V', 'W', 'X', 'Y', 'Z',
    ];

    protected $_vocali = [
        'A', 'E', 'I', 'O', 'U',
    ];

    protected $_mesi = [
        1  => 'A',  2 => 'B',  3 => 'C',  4 => 'D',  5 => 'E',
        6  => 'H',  7 => 'L',  8 => 'M',  9 => 'P', 10 => 'R',
        11 => 'S', 12 => 'T',
    ];

    protected $_pari = [
        '0' => 0, '1' =>  1, '2' =>  2, '3' =>  3, '4' =>  4,
        '5' => 5, '6' =>  6, '7' =>  7, '8' =>  8, '9' =>  9,
        'A' => 0, 'B' =>  1, 'C' =>  2, 'D' =>  3, 'E' =>  4,
        'F' => 5, 'G' =>  6, 'H' =>  7, 'I' =>  8, 'J' =>  9,
        'K' => 10, 'L' => 11, 'M' => 12, 'N' => 13, 'O' => 14,
        'P' => 15, 'Q' => 16, 'R' => 17, 'S' => 18, 'T' => 19,
        'U' => 20, 'V' => 21, 'W' => 22, 'X' => 23, 'Y' => 24,
        'Z' => 25,
    ];

    protected $_dispari = [
        '0' => 1, '1' =>  0, '2' =>  5, '3' =>  7, '4' =>  9,
        '5' => 13, '6' => 15, '7' => 17, '8' => 19, '9' => 21,
        'A' => 1, 'B' =>  0, 'C' =>  5, 'D' =>  7, 'E' =>  9,
        'F' => 13, 'G' => 15, 'H' => 17, 'I' => 19, 'J' => 21,
        'K' => 2, 'L' =>  4, 'M' => 18, 'N' => 20, 'O' => 11,
        'P' => 3, 'Q' =>  6, 'R' =>  8, 'S' => 12, 'T' => 14,
        'U' => 16, 'V' => 10, 'W' => 22, 'X' => 25, 'Y' => 24,
        'Z' => 23,
    ];

    protected $_controllo = [
        '0'  => 'A', '1'  => 'B', '2'  => 'C', '3'  => 'D',
        '4'  => 'E', '5'  => 'F', '6'  => 'G', '7'  => 'H',
        '8'  => 'I', '9'  => 'J', '10' => 'K', '11' => 'L',
        '12' => 'M', '13' => 'N', '14' => 'O', '15' => 'P',
        '16' => 'Q', '17' => 'R', '18' => 'S', '19' => 'T',
        '20' => 'U', '21' => 'V', '22' => 'W', '23' => 'X',
        '24' => 'Y', '25' => 'Z',
    ];

    protected function _scomponi($string, array $haystack)
    {
        $letters = [];
        foreach (str_split($string) as $needle) {
            if (in_array($needle, $haystack)) {
                $letters[] = $needle;
            }
        }

        return $letters;
    }

    protected function _trovaVocali($string)
    {
        return $this->_scomponi($string, $this->_vocali);
    }

    protected function _trovaConsonanti($string)
    {
        return $this->_scomponi($string, $this->_consonanti);
    }

    protected function _pulisci($string, $toupper = true)
    {
        $result = preg_replace('/[^A-Za-z]*/', '', $string);

        return ($toupper) ? strtoupper($result) : $result;
    }

    protected function _aggiungiX($string)
    {
        $code = $string;
        while (strlen($code) < 3) {
            $code .= 'X';
        }

        return $code;
    }

    protected function _calcolaNome()
    {
        if (!$this->nome) {
            throw new CodiceFiscaleException('First name not enetered');
        }
        $nome = $this->_pulisci($this->nome);

        if (strlen($nome) < 3) {
            return $this->_aggiungiX($nome);
        }
        $nome_cons = $this->_trovaConsonanti($nome);

        if (count($nome_cons) <= 3) {
            $code = implode('', $nome_cons);
        } else {
            for ($i = 0; $i < 4; $i++) {
                if ($i == 1) {
                    continue;
                }
                if (!empty($nome_cons[$i])) {
                    $code .= $nome_cons[$i];
                }
            }
        }

        if (strlen($code) < 3) {
            $nome_voc = $this->_trovaVocali($nome);
            while (strlen($code) < 3) {
                $code .= array_shift($nome_voc);
            }
        }

        return $code;
    }

    protected function _calcolaCognome()
    {
        if (!$this->cognome) {
            throw new CodiceFiscaleException('Last name not entered');
        }
        $cognome = $this->_pulisci($this->cognome);

        if (strlen($cognome) < 3) {
            return $this->_aggiungiX($cognome);
        }
        $cognome_cons = $this->_trovaConsonanti($cognome);

        $code = '';
        for ($i = 0; $i < 3; $i++) {
            if (array_key_exists($i, $cognome_cons)) {
                $code .= $cognome_cons[$i];
            }
        }

        if (strlen($code) < 3) {
            $cognome_voc = $this->_trovaVocali($cognome);
            while (strlen($code) < 3) {
                $code .= array_shift($cognome_voc);
            }
        }

        return $code;
    }

    protected function _calcolaDataNascita()
    {
        if (!$this->data) {
            throw new CodiceFiscaleException('Birth date not entered');
        }

        if (!$this->sesso) {
            throw new CodiceFiscaleException('Geneder not entered');
        }

        if ($this->data instanceof Carbon) {
            $data = $this->data;
        } else {
            $data = Carbon::createFromFormat($this->_formatoData, $this->data);
        }

        $giorno = $data->format('j');
        $mese = $data->format('n');
        $anno = $data->format('Y');

        $aa = substr($anno, -2);

        $mm = $this->_mesi[$mese];

        $gg = (strtoupper($this->sesso) == 'M') ? $giorno : $giorno + 40;
        $gg = str_pad($gg, 2, '0', STR_PAD_LEFT);

        return $aa.$mm.$gg;
    }

    protected function _calcolaCatastale()
    {
        $place = strtoupper($this->comune);
        if (array_key_exists($place, ItalianCities::list)) {
            $place_code = $place;
        } else {
            $place_code = array_search($place, ItalianCities::list);
            if (!$place_code) {
                throw new \InvalidArgumentException('$birth_place must be a valid city code or name');
            }
        }

        return $place_code;
    }

    protected function _calcolaCifraControllo($codice)
    {
        $code = str_split($codice);
        $sum = 0;
        for ($i = 1; $i <= count($code); $i++) {
            $cifra = $code[$i - 1];
            $sum += ($i % 2) ? $this->_dispari[$cifra] : $this->_pari[$cifra];
        }
        $sum %= 26;

        return $this->_controllo[$sum];
    }

    public function calcola()
    {
        $codice = $this->_calcolaCognome().
            $this->_calcolaNome().
            $this->_calcolaDataNascita().
            $this->_calcolaCatastale();
        $codice .= $this->_calcolaCifraControllo($codice);
        if (strlen($codice) != 16) {
            throw new CodiceFiscaleException('Generated code has not 16 digits: '.$codice);
        }

        return $codice;
    }

    public function formatoData($formato)
    {
        $this->_formatoData = $formato;
    }

    public function __set($key, $value)
    {
        $this->_parametri[$key] = $value;
    }

    public function __get($key)
    {
        return $this->_parametri[$key];
    }

    public function _isset($key)
    {
        return isset($this->_parametri[$key]);
    }
}
