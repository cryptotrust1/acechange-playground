<?php
/**
 * Enhanced E-E-A-T Quality Scorer.
 *
 * Scores content against Google's E-E-A-T guidelines and detects
 * AI-generated artifacts that violate quality standards.
 *
 * @package    Claude_SEO
 * @subpackage Claude_SEO/includes/ai
 */

/**
 * Comprehensive content quality scoring system.
 */
class Claude_SEO_Quality_Scorer {

    /**
     * AI artifact phrases to detect.
     *
     * @var array
     */
    private static $ai_artifacts = array(
        'delve into',
        'it\'s important to note',
        'it is important to note',
        'in conclusion',
        'in today\'s digital landscape',
        'in the ever-evolving',
        'revolutionize',
        'game-changer',
        'unlock the power',
        'comprehensive guide',
        'dive deep into',
        'let\'s explore',
        'embark on',
        'transformative journey'
    );

    /**
     * Experience signal phrases.
     *
     * @var array
     */
    private static $experience_signals = array(
        'i tested',
        'we tested',
        'in my experience',
        'we found',
        'our research',
        'we measured',
        'after testing',
        'i personally',
        'we personally',
        'i tried',
        'we tried'
    );

    /**
     * Expertise signal phrases.
     *
     * @var array
     */
    private static $expertise_signals = array(
        'study shows',
        'research indicates',
        'according to',
        'data reveals',
        'statistics show',
        'evidence suggests',
        'peer-reviewed',
        'published in',
        'years of experience'
    );

    /**
     * Authority signal phrases.
     *
     * @var array
     */
    private static $authority_signals = array(
        'cited by',
        'source:',
        'reference:',
        'expert',
        'certified',
        'licensed',
        'phd',
        'professor',
        'researcher'
    );

    /**
     * Trust signal phrases.
     *
     * @var array
     */
    private static $trust_signals = array(
        'fact-checked',
        'verified',
        'accurate as of',
        'updated',
        'transparent',
        'disclaimer',
        'methodology'
    );

    /**
     * Score content quality.
     *
     * @param string $content Content to score.
     * @param array  $context Context data (keyword, intent, etc.).
     * @return array Score and details.
     */
    public function score_content($content, $context = array()) {
        $text = wp_strip_all_tags($content);
        $text_lower = strtolower($text);

        $scores = array(
            'experience' => $this->score_experience($text_lower),
            'expertise' => $this->score_expertise($text_lower),
            'authority' => $this->score_authority($text_lower, $content),
            'trust' => $this->score_trust($text_lower, $content),
            'originality' => $this->score_originality($text_lower),
            'depth' => $this->score_depth($text, $context),
            'readability' => $this->score_readability($text)
        );

        $overall_score = $this->calculate_overall_score($scores);

        $issues = $this->identify_issues($scores, $text_lower);
        $has_ai_artifacts = $this->detect_ai_artifacts($text_lower);

        return array(
            'score' => $overall_score,
            'scores' => $scores,
            'issues' => $issues,
            'has_ai_artifacts' => $has_ai_artifacts,
            'needs_review' => $overall_score < 70 || $has_ai_artifacts,
            'recommendations' => $this->generate_recommendations($scores, $issues, $has_ai_artifacts)
        );
    }

    /**
     * Score experience signals.
     *
     * @param string $text Content text (lowercase).
     * @return int Score 0-100.
     */
    private function score_experience($text) {
        $score = 0;
        $found = 0;

        foreach (self::$experience_signals as $signal) {
            if (stripos($text, $signal) !== false) {
                $found++;
                $score += 25;
            }

            if ($found >= 4) break; // Max 100
        }

        return min(100, $score);
    }

    /**
     * Score expertise signals.
     *
     * @param string $text Content text (lowercase).
     * @return int Score 0-100.
     */
    private function score_expertise($text) {
        $score = 0;
        $found = 0;

        foreach (self::$expertise_signals as $signal) {
            if (stripos($text, $signal) !== false) {
                $found++;
                $score += 25;
            }

            if ($found >= 4) break;
        }

        // Check for specific numbers/statistics
        if (preg_match('/\d+%/', $text)) {
            $score += 10; // Has percentages
        }

        if (preg_match('/\d+ (users|customers|people|respondents)/', $text)) {
            $score += 10; // Has sample sizes
        }

        return min(100, $score);
    }

    /**
     * Score authority signals.
     *
     * @param string $text    Content text (lowercase).
     * @param string $content Original HTML content.
     * @return int Score 0-100.
     */
    private function score_authority($text, $content) {
        $score = 0;

        // Check for authority phrases
        foreach (self::$authority_signals as $signal) {
            if (stripos($text, $signal) !== false) {
                $score += 20;
            }
        }

        // Check for external links to authoritative sources
        preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $content, $links);

        if (!empty($links[1])) {
            $authoritative_domains = array('.edu', '.gov', '.org', 'wikipedia.org', 'nih.gov', 'who.int');
            $authoritative_count = 0;

            foreach ($links[1] as $url) {
                foreach ($authoritative_domains as $domain) {
                    if (stripos($url, $domain) !== false) {
                        $authoritative_count++;
                        break;
                    }
                }
            }

            $score += min(40, $authoritative_count * 10);
        }

        return min(100, $score);
    }

    /**
     * Score trust signals.
     *
     * @param string $text    Content text (lowercase).
     * @param string $content Original HTML content.
     * @return int Score 0-100.
     */
    private function score_trust($text, $content) {
        $score = 0;

        foreach (self::$trust_signals as $signal) {
            if (stripos($text, $signal) !== false) {
                $score += 20;
            }
        }

        // Check for dates (freshness indicator)
        if (preg_match('/20(2[0-9]|3[0-9])/', $text)) {
            $score += 15; // Has recent dates
        }

        // Check for author bio
        if (stripos($content, 'author') !== false || stripos($content, 'written by') !== false) {
            $score += 15;
        }

        return min(100, $score);
    }

    /**
     * Score originality (detect AI artifacts).
     *
     * @param string $text Content text (lowercase).
     * @return int Score 0-100.
     */
    private function score_originality($text) {
        $score = 100;

        // Penalize for AI artifacts
        foreach (self::$ai_artifacts as $artifact) {
            if (stripos($text, $artifact) !== false) {
                $score -= 15;
            }
        }

        // Penalize for excessive passive voice
        $passive_count = preg_match_all('/\b(was|were|been|being)\s+\w+ed\b/', $text);
        $word_count = str_word_count($text);

        if ($word_count > 0) {
            $passive_ratio = $passive_count / $word_count;

            if ($passive_ratio > 0.10) { // More than 10% passive
                $score -= 20;
            }
        }

        return max(0, $score);
    }

    /**
     * Score content depth.
     *
     * @param string $text    Content text.
     * @param array  $context Context.
     * @return int Score 0-100.
     */
    private function score_depth($text, $context) {
        $word_count = str_word_count($text);
        $score = 0;

        // Word count scoring
        if ($word_count >= 2000) {
            $score += 40;
        } elseif ($word_count >= 1000) {
            $score += 30;
        } elseif ($word_count >= 500) {
            $score += 20;
        } elseif ($word_count >= 300) {
            $score += 10;
        }

        // Check for examples
        if (stripos($text, 'example') !== false || stripos($text, 'for instance') !== false) {
            $score += 15;
        }

        // Check for case studies
        if (stripos($text, 'case study') !== false) {
            $score += 20;
        }

        // Check for data/statistics
        $number_count = preg_match_all('/\d+/', $text);
        if ($number_count > 5) {
            $score += 15; // Data-driven content
        }

        // Check for lists/structure
        if (preg_match_all('/<(ul|ol)[^>]*>/', $text) > 0) {
            $score += 10; // Has lists
        }

        return min(100, $score);
    }

    /**
     * Score readability.
     *
     * @param string $text Content text.
     * @return int Score 0-100.
     */
    private function score_readability($text) {
        $readability = Claude_SEO_Readability::analyze($text);

        if ($readability['flesch_reading_ease'] >= 60) {
            return 100;
        } elseif ($readability['flesch_reading_ease'] >= 50) {
            return 80;
        } elseif ($readability['flesch_reading_ease'] >= 40) {
            return 60;
        } else {
            return 40;
        }
    }

    /**
     * Calculate overall score.
     *
     * @param array $scores Individual scores.
     * @return int Overall score.
     */
    private function calculate_overall_score($scores) {
        $weights = array(
            'experience' => 0.20,
            'expertise' => 0.20,
            'authority' => 0.15,
            'trust' => 0.15,
            'originality' => 0.15,
            'depth' => 0.10,
            'readability' => 0.05
        );

        $weighted_score = 0;

        foreach ($scores as $key => $score) {
            $weighted_score += $score * $weights[$key];
        }

        return round($weighted_score);
    }

    /**
     * Detect AI artifacts.
     *
     * @param string $text Content text (lowercase).
     * @return bool True if artifacts found.
     */
    private function detect_ai_artifacts($text) {
        $artifact_count = 0;

        foreach (self::$ai_artifacts as $artifact) {
            if (stripos($text, $artifact) !== false) {
                $artifact_count++;
            }

            if ($artifact_count >= 2) {
                return true; // Multiple artifacts = likely AI
            }
        }

        return false;
    }

    /**
     * Identify issues.
     *
     * @param array  $scores Individual scores.
     * @param string $text   Content text.
     * @return array Issues.
     */
    private function identify_issues($scores, $text) {
        $issues = array();

        if ($scores['experience'] < 25) {
            $issues[] = 'Lacks first-hand experience signals';
        }

        if ($scores['expertise'] < 25) {
            $issues[] = 'Needs more expert knowledge and data';
        }

        if ($scores['authority'] < 25) {
            $issues[] = 'Missing authoritative sources and citations';
        }

        if ($scores['trust'] < 25) {
            $issues[] = 'Lacks trust signals (dates, author bio, fact-checking)';
        }

        if ($scores['originality'] < 70) {
            $issues[] = 'Contains AI-typical phrases or excessive passive voice';
        }

        if ($scores['depth'] < 40) {
            $issues[] = 'Content is too shallow or short';
        }

        if ($scores['readability'] < 60) {
            $issues[] = 'Readability needs improvement';
        }

        return $issues;
    }

    /**
     * Generate recommendations.
     *
     * @param array $scores          Individual scores.
     * @param array $issues          Issues identified.
     * @param bool  $has_ai_artifacts Has AI artifacts.
     * @return array Recommendations.
     */
    private function generate_recommendations($scores, $issues, $has_ai_artifacts) {
        $recommendations = array();

        if ($has_ai_artifacts) {
            $recommendations[] = 'CRITICAL: Remove AI-typical phrases and rewrite naturally';
        }

        if ($scores['experience'] < 50) {
            $recommendations[] = 'Add personal experience: "I tested...", "We found...", "In my experience..."';
        }

        if ($scores['expertise'] < 50) {
            $recommendations[] = 'Include specific data, statistics, and expert citations';
        }

        if ($scores['authority'] < 50) {
            $recommendations[] = 'Add 3-5 links to authoritative sources (.edu, .gov, research papers)';
        }

        if ($scores['trust'] < 50) {
            $recommendations[] = 'Add author credentials, update dates, and fact-checking notes';
        }

        if ($scores['depth'] < 60) {
            $recommendations[] = 'Expand content with examples, case studies, and detailed explanations';
        }

        return $recommendations;
    }
}
