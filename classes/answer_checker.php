<?php
// This file is part of Moodle - http://moodle.org/
/**
 * Answer checker for tutor-mode evaluations.
 *
 * @package    block_tutoring_machine
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/accesslib.php');

/**
 * Lightweight answer checker that evaluates learner submissions without exposing solutions.
 */
class block_tutoring_machine_answer_checker {
    /**
     * Evaluate a learner submission for a question.
     *
     * @param string|null $question_id Identifier of the question in Moodle (expected numeric).
     * @param string $student_answer Learner submission.
     * @param int|null $course_id Optional course context for sanity checks.
     * @return array Structured evaluation result (never contains the solution text).
     */
    public static function check($question_id, $student_answer, $course_id = null) {
        $student_answer = trim((string)$student_answer);
        if ($student_answer === '') {
            return [
                'result' => 'missing_submission',
                'score' => 0.0,
                'reason' => 'empty_answer'
            ];
        }

        if (empty($question_id) || !preg_match('/^\d+$/', (string)$question_id)) {
            return [
                'result' => 'unknown_question',
                'score' => 0.0,
                'reason' => 'invalid_identifier'
            ];
        }

        $question = self::load_question((int)$question_id, $course_id);
        if (!$question) {
            return [
                'result' => 'unknown_question',
                'score' => 0.0,
                'reason' => 'not_found'
            ];
        }

        $expected_answers = self::fetch_expected_answers($question);
        if (empty($expected_answers)) {
            return [
                'result' => 'no_answer_key',
                'score' => 0.0,
                'reason' => 'missing_key'
            ];
        }

        [$result, $score] = self::evaluate_similarity($student_answer, $expected_answers, $question->qtype);

        return [
            'result' => $result,
            'score' => $score,
            'source' => 'question_bank',
            'question_id' => (int)$question_id
        ];
    }

    /**
     * Load question metadata.
     *
     * @param int $question_id Question identifier.
     * @param int|null $course_id Optional course context check.
     * @return stdClass|null
     */
    private static function load_question($question_id, $course_id = null) {
        global $DB;

        $question = $DB->get_record('question', ['id' => $question_id], '*', IGNORE_MISSING);
        if (!$question) {
            return null;
        }

        if ($course_id) {
            // Verify the question belongs to the same course context (best-effort check).
            $category = $DB->get_record('question_categories', ['id' => $question->category], 'id, contextid', IGNORE_MISSING);
            if ($category) {
                $context = context::instance_by_id($category->contextid, IGNORE_MISSING);
                if ($context && ($context->contextlevel === CONTEXT_COURSE || $context->contextlevel === CONTEXT_MODULE)) {
                    if ($context->instanceid && (int)$context->instanceid !== (int)$course_id) {
                        // Question from another course or module; treat as unavailable for safety.
                        return null;
                    }
                }
            }
        }

        return $question;
    }

    /**
     * Fetch expected answers for supported question types.
     *
     * @param stdClass $question Question record.
     * @return array
     */
    private static function fetch_expected_answers($question) {
        global $DB;

        $answers = [];
        $records = $DB->get_records('question_answers', ['question' => $question->id], 'fraction DESC');
        foreach ($records as $record) {
            if ((float)$record->fraction <= 0.0) {
                continue;
            }

            $answer_text = trim(strip_tags($record->answer));
            if ($answer_text === '') {
                continue;
            }

            $answers[] = $answer_text;
        }

        // For numerical questions, fall back to dedicated table if available.
        if (empty($answers) && $question->qtype === 'numerical') {
            $numeric_records = $DB->get_records('question_numerical_answers', ['question' => $question->id]);
            foreach ($numeric_records as $numeric) {
                $answers[] = (string)$numeric->answer;
            }
        }

        return $answers;
    }

    /**
     * Evaluate similarity between the learner response and expected answers.
     *
     * @param string $student_answer Raw learner answer.
     * @param array $expected_answers List of answer strings.
     * @param string $qtype Question type identifier.
     * @return array Tuple [result, score]
     */
    private static function evaluate_similarity($student_answer, array $expected_answers, $qtype) {
        $normalized_student = self::normalize_text($student_answer);
        $best_score = 0.0;

        foreach ($expected_answers as $answer) {
            $normalized_answer = self::normalize_text($answer);
            if ($normalized_answer === '') {
                continue;
            }

            similar_text($normalized_student, $normalized_answer, $percent);
            $score = round($percent / 100, 4);
            if ($score > $best_score) {
                $best_score = $score;
            }

            // Early exit for perfect matches.
            if ($best_score >= 0.99) {
                break;
            }
        }

        // Adjust thresholds per question type where appropriate.
        $match_threshold = 0.9;
        $partial_threshold = 0.6;
        if ($qtype === 'numerical') {
            $match_threshold = 0.95;
            $partial_threshold = 0.75;
        }

        if ($best_score >= $match_threshold) {
            return ['match', $best_score];
        }

        if ($best_score >= $partial_threshold) {
            return ['partial', $best_score];
        }

        return ['missing_points', $best_score];
    }

    /**
     * Normalize text for comparison.
     *
     * @param string $text Input text.
     * @return string Normalized text.
     */
    private static function normalize_text($text) {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $text = strip_tags($text);
        $text = core_text::strtolower($text);
        $text = preg_replace('/\s+/u', ' ', $text);
        return trim($text);
    }
}
