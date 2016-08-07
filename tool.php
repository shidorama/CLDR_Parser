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
     * Loads CLDR data from xml file using SimpleXML,
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
        $data = NULL;
        $xml = NULL;
    }

    /**
     * @param $language
     * @param $population
     * @return bool
     */
    private function pushLanguageData($language, $population)
    {
        $population = (int)$population;
        if (isset($this->output[$language])) {
            $this->output[$language]['population'] += $population;
            return true;
        }
        $language = (string)$language;
        $dn = Locale::getDisplayName($language, 'en');
        if ($dn == $language)
            return false;
        $this->output[$language]['name'] = $dn;
        $this->output[$language]['population'] = $population;
        return true;
    }

    /**
     * @return $this
     */
    public function calculateLanguageData()
    {
        foreach ($this->territoryData->territory as $terr) {
            $population = (int)$terr['population'];
            foreach ($terr->languagePopulation as $lp) {
                $langPopulationPrecent = (int)$lp['populationPercent'];
                $langPopulation = round($population * $langPopulationPrecent / 100);
                $langCode = strtoupper((string)$lp['type']);
                $this->pushLanguageData($langCode, $langPopulation);
            }
        }
        return $this;
    }

    /**
     * @return CLDRParser
     */
    public function printOutput()
    {
        foreach ($this->output as $name => $data) {
            echo "$name {$data['name']} {$data['population']}\r\n";
        }
        return $this;
    }


}
try {
    $x = new CLDRParser('asdas');
    $x->calculateLanguageData()->printOutput();
} catch (Exception $e) {
    $msg = $e->getMessage();
    echo 'ERROR: '.$msg;
}
