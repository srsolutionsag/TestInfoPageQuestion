<?php

require_once('./Modules/TestQuestionPool/classes/class.ilQuestionsPlugin.php');

/**
 * Class ilTestInfoPageQuestionPlugin
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class ilTestInfoPageQuestionPlugin extends ilQuestionsPlugin {

	const TEST_INFO_PAGE_QUESTION = 'TestInfoPageQuestion';


	/**
	 * @return string
	 */
	public function getPluginName() {
		return self::TEST_INFO_PAGE_QUESTION;
	}


	/**
	 * @return string
	 */
	public function getQuestionType() {
		return self::TEST_INFO_PAGE_QUESTION;
	}


	/**
	 * @return string
	 */
	public function getQuestionTypeTranslation() {
		return $this->txt('common_question_type');
	}


	public function updateLanguageFiles() {
		ini_set('auto_detect_line_endings', true);
		$path = substr(__FILE__, 0, strpos(__FILE__, 'classes')) . 'lang/';
		if (file_exists($path . 'lang_custom.csv')) {
			$file = $path . 'lang_custom.csv';
		} else {
			$file = $path . 'lang.csv';
		}
		$keys = array();
		$new_lines = array();

		foreach (file($file) as $n => $row) {
			if ($n == 0) {
				$keys = str_getcsv($row, ";");
				continue;
			}
			$data = str_getcsv($row, ";");;
			foreach ($keys as $i => $k) {
				if ($k != 'var' AND $k != 'part') {
					$new_lines[$k][] = $data[0] . '_' . $data[1] . '#:#' . $data[$i];
				}
			}
		}
		$start = '<!-- language file start -->' . PHP_EOL;
		$status = true;

		foreach ($new_lines as $lng_key => $lang) {
			$status = file_put_contents($path . 'ilias_' . $lng_key . '.lang', $start . implode(PHP_EOL, $lang));
		}

		if (! $status) {
			ilUtil::sendFailure('Language-Files could not be written');
		}
		$this->updateLanguages();
	}
}

?>
