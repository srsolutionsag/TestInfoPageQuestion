<?php
require_once('./Services/RTE/classes/class.ilRTE.php');
require_once('./Modules/TestQuestionPool/classes/class.assQuestion.php');
require_once('./Services/RTE/classes/class.ilRTE.php');
include_once 'Modules/Test/classes/class.ilObjTestAccess.php';
include_once 'Services/Tracking/classes/class.ilLPStatusWrapper.php';

/**
 * Class TestInfoPageQuestion
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class TestInfoPageQuestion extends assQuestion
{
    
    /**
     * @var boolean Indicates whether completion by submission is enabled or not
     */
    protected $completion_by_submission = false;
    
    public function deleteGenericFeedbacks()
    {
        return true;
    }
    
    /**
     * Returns true, if the question is complete for use
     *
     * @return boolean True, if the question is complete for use, otherwise false
     */
    public function isComplete()
    {
        if (strlen($this->title)
            && ($this->author)
            && ($this->question)
            && ($this->getMaximumPoints() >= 0)
            && is_numeric($this->getMaximumPoints())
        ) {
            return true;
        }
        
        return false;
    }
    
    /**
     * @return bool
     */
    public function isCompletionBySubmissionEnabled()
    {
        return true;
    }
    
    /**
     * @param string $original_id
     * Saves a assFileUpload object to a database
     */
    public function saveToDb($original_id = "")
    {
        $this->saveQuestionDataToDb($original_id);
        parent::saveToDb();
    }
    
    /**
     * @param int $question_id
     */
    public function loadFromDb($question_id)
    {
        /**
         * @var $ilDB ilDB
         */
        global $ilDB;
        $sql = 'SELECT * FROM qpl_questions WHERE question_id = '
            . $ilDB->quote($question_id, 'integer');;
        $set = $ilDB->query($sql);
        if ($ilDB->numRows($set)) {
            $data = $ilDB->fetchAssoc($set);
            $this->setId($question_id);
            $this->setObjId($data["obj_fi"]);
            $this->setTitle($data["title"]);
            $this->setComment($data["description"]);
            $this->setOriginalId($data["original_id"]);
            $this->setNrOfTries($data['nr_of_tries']);
            $this->setAuthor($data["author"]);
            $this->setPoints($data["points"]);
            $this->setOwner($data["owner"]);
            $this->setQuestion(ilRTE::_replaceMediaObjectImageSrc($data["question_text"], 1));
            $this->setShuffle($data["shuffle"]);
            $this->matchcondition = (strlen($data['matchcondition'])) ? $data['matchcondition'] : 0;
            $this->setEstimatedWorkingTime(
                substr($data["working_time"], 0, 2),
                substr($data["working_time"], 3, 2),
                substr($data["working_time"], 6, 2)
            );
        }
        
        parent::loadFromDb($question_id);
    }
    
    /**
     * @param bool   $for_test
     * @param string $title
     * @param string $author
     * @param string $owner
     * @param null   $testObjId
     *
     * @return int
     */
    public function duplicate($for_test = true, $title = "", $author = "", $owner = "", $testObjId = null)
    {
        if ($this->id <= 0) {
            return false;
        }
        // duplicate the question in database
        $this_id = $this->getId();
        
        if ((int) $testObjId > 0) {
            $thisObjId = $this->getObjId();
        }
        
        $clone = $this;
        $original_id = assQuestion::_getOriginalId($this->id);
        $clone->id = -1;
        
        if ((int) $testObjId > 0) {
            $clone->setObjId($testObjId);
        }
        
        if ($title) {
            $clone->setTitle($title);
        }
        
        if ($author) {
            $clone->setAuthor($author);
        }
        if ($owner) {
            $clone->setOwner($owner);
        }
        
        if ($for_test) {
            $clone->saveToDb($original_id);
        } else {
            $clone->saveToDb();
        }
        
        // copy question page content
        $clone->copyPageOfQuestion($this_id);
        // copy XHTML media objects
        $clone->copyXHTMLMediaObjectsOfQuestion($this_id);
        
        $clone->onDuplicate($thisObjId, $this_id, $clone->getObjId(), $clone->getId());
        
        return $clone->id;
    }
    
    /**
     * @param        $target_questionpool_id
     * @param string $title
     *
     * @return bool|int
     */
    public function copyObject($target_questionpool_id, $title = '')
    {
        if ($this->id <= 0) {
            return false;
        }
        // duplicate the question in database
        $clone = $this;
        $original_id = assQuestion::_getOriginalId($this->id);
        $clone->id = -1;
        $source_questionpool_id = $this->getObjId();
        $clone->setObjId($target_questionpool_id);
        if ($title) {
            $clone->setTitle($title);
        }
        $clone->saveToDb();
        
        // copy question page content
        $clone->copyPageOfQuestion($original_id);
        // copy XHTML media objects
        $clone->copyXHTMLMediaObjectsOfQuestion($original_id);
        
        $clone->onCopy($source_questionpool_id, $original_id, $clone->getObjId(), $clone->getId());
        
        return $clone->id;
    }
    
    /**
     * @param        $targetParentId
     * @param string $targetQuestionTitle
     *
     * @return bool|int
     */
    public function createNewOriginalFromThisDuplicate($targetParentId, $targetQuestionTitle = "")
    {
        if ($this->id <= 0) {
            return false;
        }
        
        $sourceQuestionId = $this->id;
        $sourceParentId = $this->getObjId();
        
        // duplicate the question in database
        $clone = $this;
        $clone->id = -1;
        
        $clone->setObjId($targetParentId);
        
        if ($targetQuestionTitle) {
            $clone->setTitle($targetQuestionTitle);
        }
        
        $clone->saveToDb();
        // copy question page content
        $clone->copyPageOfQuestion($sourceQuestionId);
        // copy XHTML media objects
        $clone->copyXHTMLMediaObjectsOfQuestion($sourceQuestionId);
        
        $clone->onCopy($sourceParentId, $sourceQuestionId, $clone->getObjId(), $clone->getId());
        
        return $clone->id;
    }
    
    /**
     * Returns the maximum points, a learner can reach answering the question
     *
     * @see $points
     */
    public function getMaximumPoints()
    {
        return $this->getPoints();
    }
    
    /**
     * Returns the points, a learner has reached answering the question.
     * The points are calculated from the given answers.
     *
     * @access public
     *
     * @param integer $active_id
     * @param integer $pass
     * @param bool    $authorizedSolution
     * @param boolean $returndetails (deprecated !!)
     * @return int /array $points/$details (array $details is deprecated !!)
     */
    public function calculateReachedPoints($active_id, $pass = null, $authorizedSolution = true, $returndetails = false)
    {
        return 0;
    }
    
    /**
     * Saves the learners input of the question to the database.
     *
     * @access public
     *
     * @param integer $active_id Active id of the user
     * @param integer $pass      Test pass
     *
     * @param bool    $authorized
     * @return bool $status
     */
    public function saveWorkingData($active_id, $pass = null, $authorized = true)
    {
        return true;
    }
    
    /**
     * Reworks the allready saved working data if neccessary
     *
     * @access protected
     *
     * @param integer $active_id
     * @param integer $pass
     * @param boolean $obligationsAnswered
     * @param bool    $authorized
     */
    protected function reworkWorkingData($active_id, $pass, $obligationsAnswered, $authorized)
    {
        $this->handleSubmission($active_id, $pass, $obligationsAnswered);
    }
    
    /**
     * This method is called after an user submitted one or more files.
     * It should handle the setting "Completion by Submission" and, if enabled, set the status of
     * the current user.
     *
     * @param $active_id
     * @param $pass
     * @param $obligationsAnswered
     *
     * @internal  param $integer
     * @internal  param $integer
     *
     * @access    protected
     */
    protected function handleSubmission($active_id, $pass, $obligationsAnswered)
    {
        if ($this->isCompletionBySubmissionEnabled()) {
            $maxpoints = assQuestion::_getMaximumPoints($this->getId());
            assQuestion::_setReachedPoints($active_id, $this->getId(), 0, $maxpoints, $pass, 1, $obligationsAnswered);
            ilLPStatusWrapper::_updateStatus(
                ilObjTest::_getObjectIDFromActiveID((int) $active_id),
                ilObjTestAccess::_getParticipantId((int) $active_id)
            );
        }
    }
    
    /**
     * //     * @return string
     * //     */
    public function getQuestionType()
    {
        $plugin_object = new ilTestInfoPageQuestionPlugin();
        
        return $plugin_object->getQuestionType();
    }
    
    /**
     * @return string
     */
    public function getAdditionalTableName()
    {
        return "qpl_qst_fileupload";
    }
    
    /**
     * @return string
     */
    public function getAnswerTableName()
    {
        return "";
    }
    
    /**
     * @param int $question_id
     */
    public function deleteAnswers($question_id)
    {
    }
    
    /**
     * @param object $worksheet
     * @param object $startrow
     * @param object $active_id
     * @param object $pass
     * @return object
     */
    public function setExportDetailsXLS($worksheet, $startrow, $active_id, $pass)
    {
        
        return $startrow + 1;
    }
    
    /**
     * @param object $item
     * @param int    $questionpool_id
     * @param int    $tst_id
     * @param object $tst_object
     * @param int    $question_counter
     * @param array  $import_mapping
     */
    public function fromXML(&$item, &$questionpool_id, &$tst_id, &$tst_object, &$question_counter, &$import_mapping)
    {
        // TODO Import XML
    }
    
    /**
     * string The QTI xml representation of the question
     *
     * @param bool $a_include_header
     * @param bool $a_include_binary
     * @param bool $a_shuffle
     * @param bool $test_output
     * @param bool $force_image_references
     * @return string|void
     */
    public function toXML(
        $a_include_header = true,
        $a_include_binary = true,
        $a_shuffle = false,
        $test_output = false,
        $force_image_references = false
    ) {
        // TODO Export XML
    }
    
    /**
     * @param $active_id
     * @param $pass
     *
     * @return array
     */
    public function getBestSolution($active_id, $pass)
    {
        return [];
    }
    
    /**
     * @param int      $active_id
     * @param int|null $pass
     *
     * @return bool
     */
    public function isAnswered($active_id, $pass = null)
    {
        $answered = self::doesSolutionRecordsExist($active_id, $pass, $this->getId());
        
        return $answered;
    }
    
    /**
     * @param $active_id
     * @param $pass
     * @param $qid
     * @return bool
     */
    protected static function doesSolutionRecordsExist($active_id, $pass, $qid)
    {
        global $ilDB;
        $query = "SELECT COUNT(active_fi) cnt FROM tst_solutions WHERE active_fi = %s AND question_fi = %s AND pass = %s";
        $res = $ilDB->queryF($query, ['integer', 'integer', 'integer'], [
            $active_id,
            $qid,
            $pass,
        ]);
        $row = $ilDB->fetchAssoc($res);
        
        return 0 < (int) $row['cnt'];
    }
    
    /**
     * @param int $questionId
     *
     * @return bool
     */
    public static function isObligationPossible($questionId)
    {
        return true;
    }
    
    /**
     * @return bool
     */
    public function isAutosaveable()
    {
        return false;
    }
    
    public function getSolutionSubmit()
    {
        return null;
    }
    
    public function calculateReachedPointsForSolution()
    {
        return 0;
    }
}
