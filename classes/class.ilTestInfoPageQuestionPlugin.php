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
}

