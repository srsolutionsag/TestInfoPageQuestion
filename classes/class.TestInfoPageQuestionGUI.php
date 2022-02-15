<?php
require_once('./Customizing/global/plugins/Modules/TestQuestionPool/Questions/TestInfoPageQuestion/classes/class.TestInfoPageQuestion.php');
require_once('./Modules/TestQuestionPool/classes/class.assQuestionGUI.php');
require_once('./Services/Form/classes/class.ilPropertyFormGUI.php');
require_once('./Services/AdvancedEditing/classes/class.ilObjAdvancedEditing.php');

/**
 * Class TestInfoPageQuestionGUI
 *
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 *
 * @ilCtrl_IsCalledBy TestInfoPageQuestionGUI: ilObjQuestionPoolGUI, ilObjTestGUI
 * @ilCtrl_IsCalledBy TestInfoPageQuestionGUI: ilQuestionEditGUI, ilTestExpressPageObjectGUI
 * @ilCtrl_Calls      TestInfoPageQuestionGUI: ilFormPropertyDispatchGUI
 */
class TestInfoPageQuestionGUI extends assQuestionGUI
{
    
    const FIELD_NAME = 'field_info_page';
    /**
     * @var ilTestInfoPageQuestionPlugin
     */
    protected $plugin_object;
    /**
     * @var TestInfoPageQuestion
     */
    public $object;
    /**
     * @var ilCtrl
     */
    public $ctrl;
    /**
     * @var int
     */
    public $id;
    
    /**
     * @param $a_id
     */
    public function __construct($a_id = -1)
    {
        $this->id = $a_id;
        parent::__construct();
        $this->plugin_object = new ilTestInfoPageQuestionPlugin();
        $this->initObject();
    }
    
    protected function initObject()
    {
        $this->object = new TestInfoPageQuestion();
        if ($this->id >= 0) {
            $this->object->loadFromDb($this->id);
        }
    }
    
    /**
     * Evaluates a posted edit form and writes the form data in the question object
     *
     * @param bool $always
     *
     * @return integer A positive value, if one of the required fields wasn't set, else 0
     */
    public function writePostData($always = false)
    {
        $hasErrors = (!$always) ? $this->editQuestion(true) : false;
        if (!$hasErrors) {
            $this->writeQuestionGenericPostData();
            $this->saveTaxonomyAssignments();
            
            return 0;
        }
        
        return 1;
    }
    
    /**
     * Creates an output of the edit form for the question
     *
     * @access public
     *
     * @param bool $checkonly
     *
     * @return bool
     */
    public function editQuestion($checkonly = false)
    {
        $save = $this->isSaveCommand();
        $this->getQuestionTemplate();
        
        include_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
        $form = new ilPropertyFormGUI();
        $form->setFormAction($this->ctrl->getFormAction($this));
        $form->setTitle($this->outQuestionType());
        $form->setMultipart(false);
        $form->setTableWidth("100%");
        $form->setId("assfileupload");
        
        $this->addBasicQuestionFormProperties($form);
        $this->populateQuestionSpecificFormPart($form);
        
        $this->populateTaxonomyFormSection($form);
        $this->addQuestionFormCommandButtons($form);
        
        $errors = false;
        
        if ($save) {
            $form->setValuesByPost();
            $errors = !$form->checkInput();
            $form->setValuesByPost(); // again, because checkInput now performs the whole stripSlashes handling and
            // we need this if we don't want to have duplication of backslashes
            if ($errors) {
                $checkonly = false;
            }
        }
        
        if (!$checkonly) {
            $this->tpl->setVariable("QUESTION_DATA", $form->getHTML());
        }
        
        return $errors;
    }
    
    /**
     * @param ilPropertyFormGUI $form
     *
     * @return ilPropertyFormGUI
     */
    public function populateQuestionSpecificFormPart(ilPropertyFormGUI $form)
    {
        /**
         * @var $finalstatement ilTextAreaInputGUI
         */
        $finalstatement = $form->getItemByPostVar('question');
        $finalstatement->setTitle($this->plugin_object->txt(self::FIELD_NAME));
        $finalstatement->setValue($this->object->prepareTextareaOutput($this->object->getQuestion()));
        $finalstatement->setRows(30);
        $finalstatement->setUseRte(true);
        $finalstatement->setRteTags(ilObjAdvancedEditing::_getUsedHTMLTags('test'));
        $finalstatement->addPlugin('latex');
        $finalstatement->addButton('latex');
        $finalstatement->addButton('pastelatex');
        $finalstatement->setRTESupport($this->object->getId(), 'tst', 'test', null, true);
        
        //		$form->addItem($finalstatement);
        
        return $form;
    }
    
    public function addTab_QuestionPreview(ilTabsGUI $tabsGUI)
    {
    }
    
    
    /**
     * @param      $formaction
     * @param      $active_id
     * @param null $pass
     * @param bool $is_postponed
     * @param bool $use_post_solutions
     * @param bool $show_feedback
     *
     * @deprecated
     */
    //	public function outQuestionForTest($formaction, $active_id, $pass = NULL, $is_postponed = false, $use_post_solutions = false, $show_feedback = false) {
    //		$questionoutput = $this->object->getQuestion();
    //		$pageoutput = $this->outQuestionPage("", $is_postponed, $active_id, $questionoutput);
    //		$this->tpl->setVariable("QUESTION_OUTPUT", $pageoutput);
    //		$this->tpl->setVariable("FORMACTION", $formaction);
    //	}
    
    /**
     * @param        $a_temp_var
     * @param bool   $a_postponed
     * @param string $active_id
     * @param string $html
     * @param bool   $inlineFeedbackEnabled
     *
     * @return mixed|string
     */
    public function outQuestionPage(
        $a_temp_var,
        $a_postponed = false,
        $active_id = "",
        $html = "",
        $inlineFeedbackEnabled = false
    ) {
        $postponed = "";
        if ($a_postponed) {
            $postponed = " (" . $this->lng->txt("postponed") . ")";
        }
        
        include_once("./Modules/TestQuestionPool/classes/class.ilAssQuestionPageGUI.php");
        $this->lng->loadLanguageModule("content");
        $page_gui = new ilAssQuestionPageGUI($this->object->getId());
        $page_gui->setTemplateTargetVar($a_temp_var);
        if (strlen($html)) {
            $page_gui->setQuestionHTML([$this->object->getId() => $html]);
        }
        
        $page_gui->setOutputMode("print");
        
        include_once "./Modules/Test/classes/class.ilObjTest.php";
        $ilObjTest = new ilObjTest();
        $title_output = $ilObjTest->_getTitleOutput($active_id);
        
        if ($this->object->areObligationsToBeConsidered()
            && ilObjTest::isQuestionObligatory($this->object->getId())
        ) {
            $obligatoryString = '([-_-])';
        } else {
            $obligatoryString = '';
        }
        
        switch ($title_output) {
            case 2:
                $page_gui->setPresentationTitle(
                    $postponed . $obligatoryString
                );
                break;
            case 0:
            case 1:
            default:
                $page_gui->setPresentationTitle(
                    $this->object->getTitle() . $postponed . $obligatoryString
                );
                break;
        }
        
        $question_info_tpl = new ilTemplate('tpl.tst_question_info.html', true, true, 'Modules/Test');
        $question_position = sprintf(
            $this->lng->txt("tst_position"),
            $this->getSequenceNumber(),
            $this->getQuestionCount()
        );
        $question_info_tpl->setVariable('TXT_POSITION_POINTS', $question_position);
        $page_gui->setQuestionInfoHTML($question_info_tpl->get());
        
        $presentation = $page_gui->presentation();
        if (strlen($obligatoryString)) {
            $replacement = '<br><span class="obligatory" style="font-size:small">'
                . $this->lng->txt("tst_you_have_to_answer_this_question") . '</span>';
            $presentation = str_replace($obligatoryString, $replacement, $presentation);
        }
        $presentation = preg_replace(
            "/src=\"\\.\\//ims",
            "src=\"" . ILIAS_HTTP_PATH
            . "/",
            $presentation
        );
        return str_replace('ilc_question_Standard', '', $presentation);
    }
    
    /**
     * Get the question solution output
     *
     * @param integer $active_id             The active user id
     * @param integer $pass                  The test pass
     * @param boolean $graphicalOutput       Show visual feedback for right/wrong answers
     * @param boolean $result_output         Show the reached points for parts of the question
     * @param boolean $show_question_only    Show the question without the ILIAS content around
     * @param boolean $show_feedback         Show the question feedback
     * @param boolean $show_correct_solution Show the correct solution instead of the user solution
     * @param boolean $show_manual_scoring   Show specific information for the manual scoring output
     *
     * @param bool    $show_question_text
     *
     * @return string The solution output of the question as HTML code
     */
    public function getSolutionOutput(
        $active_id,
        $pass = null,
        $graphicalOutput = false,
        $result_output = false,
        $show_question_only = true,
        $show_feedback = false,
        $show_correct_solution = false,
        $show_manual_scoring = false,
        $show_question_text = true
    ) {
    
        return '';
    }
    
    /**
     * @param bool $show_question_only
     *
     * @param bool $showInlineFeedback
     *
     * @return string
     */
    public function getPreview($show_question_only = false, $showInlineFeedback = false)
    {
        return $this->object->getQuestion();
    }
    
    /**
     * @param      $active_id
     * @param null $pass
     * @param bool $is_postponed
     * @param bool $use_post_solutions
     * @param bool $show_feedback
     *
     * @return string
     */
    public function getTestOutput(
        $active_id,
        $pass = null,
        $is_postponed = false,
        $use_post_solutions = false,
        $show_feedback = false
    ) {
        include_once "./Services/UICore/classes/class.ilTemplate.php";
        $template = $this->plugin_object->getTemplate("tpl.il_as_qpl_info_page_output.html");
        $questiontext = $this->object->getQuestion();
        $template->setVariable("QUESTIONTEXT", $this->object->prepareTextareaOutput($questiontext, true));
        $questionoutput = $template->get();
        return $this->outQuestionPage("", $is_postponed, $active_id, $questionoutput);
    }
    
    /**
     * Sets the ILIAS tabs for this question type
     *
     * @access public
     *
     * @todo   :    MOVE THIS STEPS TO COMMON QUESTION CLASS assQuestionGUI
     */
    public function setQuestionTabs()
    {
        global $rbacsystem, $ilTabs;
        
        $this->ctrl->setParameterByClass("ilAssQuestionPageGUI", "q_id", $_GET["q_id"]);
        include_once "./Modules/TestQuestionPool/classes/class.assQuestion.php";
        $q_type = $this->object->getQuestionType();
        
        if (strlen($q_type)) {
            $classname = $q_type . "GUI";
            $this->ctrl->setParameterByClass(strtolower($classname), "sel_question_types", $q_type);
            $this->ctrl->setParameterByClass(strtolower($classname), "q_id", $_GET["q_id"]);
        }
        
        if ($_GET["q_id"]) {
            if ($rbacsystem->checkAccess('write', $_GET["ref_id"])) {
                // edit page
                $ilTabs->addTarget(
                    "edit_page",
                    $this->ctrl->getLinkTargetByClass("ilAssQuestionPageGUI", "edit"),
                    [
                        "edit",
                        "insert",
                        "exec_pg",
                    ],
                    "",
                    "",
                    false
                );
            }
            
            $this->addTab_QuestionPreview($ilTabs);
        }
        
        if ($rbacsystem->checkAccess('write', $_GET["ref_id"])) {
            $url = "";
            if ($classname) {
                $url = $this->ctrl->getLinkTargetByClass($classname, "editQuestion");
            }
            // edit question properties
            $ilTabs->addTarget(
                "edit_question",
                $url,
                [
                    "editQuestion",
                    "save",
                    "cancel",
                    "saveEdit",
                ],
                $classname,
                ""
            );
        }
        
        // add tab for question feedback within common class assQuestionGUI
        $this->addTab_QuestionFeedback($ilTabs);
        
        // add tab for question hint within common class assQuestionGUI
        $this->addTab_QuestionHints($ilTabs);
        
        if ($_GET["q_id"]) {
            $ilTabs->addTarget(
                "solution_hint",
                $this->ctrl->getLinkTargetByClass($classname, "suggestedsolution"),
                [
                    "suggestedsolution",
                    "saveSuggestedSolution",
                    "outSolutionExplorer",
                    "cancel",
                    "addSuggestedSolution",
                    "cancelExplorer",
                    "linkChilds",
                    "removeSuggestedSolution",
                ],
                $classname,
                ""
            );
        }
        
        // Assessment of questions sub menu entry
        if ($_GET["q_id"]) {
            $ilTabs->addTarget(
                "statistics",
                $this->ctrl->getLinkTargetByClass($classname, "assessment"),
                ["assessment"],
                $classname,
                ""
            );
        }
        
        if (($_GET["calling_test"] > 0) || ($_GET["test_ref_id"] > 0)) {
            $ref_id = $_GET["calling_test"];
            if (strlen($ref_id) == 0) {
                $ref_id = $_GET["test_ref_id"];
            }
            
            global $___test_express_mode;
            
            if (!$_GET['test_express_mode'] && !$___test_express_mode) {
                $ilTabs->setBackTarget(
                    $this->lng->txt("backtocallingtest"),
                    "ilias.php?baseClass=ilObjTestGUI&cmd=questions&ref_id=$ref_id"
                );
            } else {
                $link = ilTestExpressPage::getReturnToPageLink();
                $ilTabs->setBackTarget($this->lng->txt("backtocallingtest"), $link);
            }
        } else {
            $ilTabs->setBackTarget(
                $this->lng->txt("qpl"),
                $this->ctrl->getLinkTargetByClass("ilobjquestionpoolgui", "questions")
            );
        }
    }
    
    /**
     * Returns the answer specific feedback for the question
     *
     * This method should be overwritten by the actual question.
     *
     * @param array $userSolution ($userSolution[<value1>] = <value2>)
     *
     * @return string HTML Code with the answer specific feedback
     * @access public
     * @todo   Mark this method abstract!
     */
    public function getSpecificFeedbackOutput($userSolution)
    {
        $output = "";
        
        return $this->object->prepareTextareaOutput($output, true);
    }
    
    /**
     * @return array
     */
    public function getAfterParticipationSuppressionQuestionPostVars()
    {
        return [];
    }
    
    /**
     * @param $relevant_answers
     *
     * @return string
     */
    public function getAggregatedAnswersView($relevant_answers)
    {
        return '';
    }
}
