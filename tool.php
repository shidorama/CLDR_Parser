<?php
/**
 * Created by PhpStorm.
 * User: Shido
 * Date: 07.08.2016
 * Time: 21:52
 */
class CLDRParser
{
    private $output = array();
    private $territoryData;

    /**
     * CLDRParser constructor.
     * Loads CLDR data from xml file using SimpleXML, throws errors if
     * @param string|NULL $filename
     * @throws Exception
     */
    public function __construct($filename = NULL)
    {
        if (!$filename)
            $filename = 'supplementalData.xml';
        if (!file_exists($filename))
            throw new InvalidArgumentException('File does not exist! Cannot load file');
        if (!$fp = fopen($filename, 'r'))
            throw new Exception('Cannot open data file!');
        $data = '';
        while ($str = fgets($fp)) {
            $data .= $str;
        }
        $xml = new SimpleXMLElement($data);
        $this->territoryData = $xml->territoryInfo;
        if (!$this->territoryData)
            throw new Exception('Required field absent!');
    }

    /**
     * Method adds prepared language data to instance variable
     * @param string $language
     * @param int $population
     * @return void
     */
    private function pushLanguageData($language, $population)
    {
        $population = (int)$population;
        if (isset($this->output[$language])) {
            $this->output[$language]['population'] += $population;
            return;
        }
        $language = (string)$language;
        $dn = Locale::getDisplayName($language, 'en');
        if (strtolower($dn) == strtolower($language))
            trigger_error("Unable to find language name for code $dn", E_USER_WARNING);
        $this->output[$language]['name'] = $dn;
        $this->output[$language]['population'] = $population;
    }


    /**
     * Method iterates through language data and calculates overall speakers for language
     * @return $this
     */
    public function calculateLanguageData()
    {
        foreach ($this->territoryData->territory as $terr) {
            if (!$terr) {
                trigger_error("Got empty territory data!", E_USER_WARNING);
                continue;
            }
            $population = (int)$terr['population'];
            if ($population == 0)
                continue;
            foreach ($terr->languagePopulation as $lp) {
                $langPopulationPercent = $lp['populationPercent']?(int)$lp['populationPercent']:NULL;
                $langCode = $lp['type']?strtoupper((string)$lp['type']):NULL;
                if (!($langCode or $langPopulationPercent)) {
                    trigger_error("Missing data in language population leaf!", E_USER_WARNING);
                    continue;
                }
                $langPopulation = round($population * $langPopulationPercent / 100);
                $this->pushLanguageData($langCode, $langPopulation);
            }
        }
        return $this;
    }

    /**
     * Prints resulting language data
     * @return CLDRParser
     */
    public function printOutput()
    {
        foreach ($this->output as $name => $data) {
            echo "$name {$data['name']} {$data['population']}\r\n";
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getOutput()
    {
        return $this->output;
    }



}

$opts = ['data::', 'help::', 'verbose'];
$clArgs = getopt('' ,$opts);
if (isset($clArgs['help']))
    echo "Usage: tool.php [--data=<datafile>] [--verbose]\r\n";
else {
    if (isset($clArgs['verbose'])) {
        error_reporting(E_ALL);
    } else {
        error_reporting(E_ERROR);
    }
    try {
        $data = NULL;
        if (isset($clArgs['data']))
            $data = (string) $clArgs['data'];
        $x = new CLDRParser($data);
        $x->calculateLanguageData()->printOutput();
    } catch (Exception $e) {
        $msg = $e->getMessage();
        echo 'ERROR: '.$msg;
    }
}

