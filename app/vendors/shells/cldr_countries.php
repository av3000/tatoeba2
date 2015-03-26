<?php
/**
 *  Tatoeba Project, free collaborative creation of languages corpuses project
 *  Copyright (C) 2015  Gilles Bedel
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

App::import('Model', 'Country');

class CLDRCountriesShell extends Shell {

    private function download_ldml($from, $to) {
        echo "Downloading $from... ";
        $ldml = @file_get_contents($from);
        if (!$ldml) {
            echo "failed\n";
            return false;
        }
        if (!@file_put_contents($to, $ldml)) {
            echo "can't write file!\n";
            return false;
        }
        echo "done\n";
        return true;
    }

    private function get_ldml($locale_id) {
        $filename = $locale_id.'.xml';
        $file = TMP.$filename;
        if (file_exists($file)) {
            echo "Using cached file $file\n";
        } else {
            $url = 'http://unicode.org/cldr/trac/export/HEAD/trunk/common/main/'.$filename;
            if (!$this->download_ldml($url, $file)) {
                die("Error: the '$locale_id' LDML file is required.\n");
            }
        }
        return $file;
    }
    
    private function countries_from_ldml_file($filename) {
        $countries_trans = array();
        $ldml = simplexml_load_file($filename, 'SimpleXMLElement');
        foreach ($ldml->{'localeDisplayNames'}->{'territories'}->{'territory'}
                 as $country_trans) {
            $translated_into = trim($country_trans->attributes()->{'type'});
            if (is_numeric($translated_into) || $country_trans->attributes()->{'alt'} || $translated_into == 'ZZ') {
                continue;
            }
            $countries_trans["$translated_into"] = "$country_trans";
        }
        return $countries_trans;
    }

    private function countries_array_to_php($countries) {
        $lines = array();
        foreach ($countries as $code => $name) {
            $name = preg_replace("/'/", "\\'", $name);
            $lines[] = sprintf("array('Country' => array('id' => '%s', 'name' => __d('countries', '%s', true)))", $code, $name);
        }
        return implode(",\n            ", $lines);
    }

    private function CLDR_to_PHP_array($lang) {
        $ldml_file = $this->get_ldml($lang);
        $countries = $this->countries_from_ldml_file($ldml_file);
        $countries_as_php = $this->countries_array_to_php($countries);

        $php_file = APP.'models'.DS."country_$lang.php";
        $fh = fopen($php_file, 'w');
        $our_name = get_class($this);
        fprintf($fh, "<?php
/**
 * This file has been autogenerated by $our_name.
 * Consider using it if you want to update this file.
 */
class Country_$lang {
    public \$data;

    public function __construct() {
        \$this->data = array(
            $countries_as_php
        );
    }
}
");
        fclose($fh);
        print("Wrote $php_file.\n");
    }

    private function CLDR_to_po($cldr_code, $tatoeba_code) {
    }

    private function die_usage() {
        $this_script = basename(__FILE__, '.php');
        die("\nThis script generates the country list in English (as PHP code) when given the 'eng' parameter. It also generates its translation into various languages based on data from the CLDR project (as PO file).\n\n".
"  Usage: $this_script <2-letters-CLDR-code> <3-letters-tatoeba-ui-code>\n".
"Example: $this_script es spa\n");
    }

    public function main() {
        if (count($this->args) == 0) {
            $this->die_usage();
        }
        $cldr_code = $this->args[0];
        if ($cldr_code == 'en' || $cldr_code == 'eng') {
            $this->CLDR_to_PHP_array('en');
            exit;
        }

        if (count($this->args) < 2) {
            $this->die_usage();
        }
        $tatoeba_code = $this->args[1];
        $this->CLDR_to_po($cldr_code, $tatoeba_code);
    }
}
