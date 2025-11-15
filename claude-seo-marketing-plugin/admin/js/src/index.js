/**
 * Claude SEO Admin JavaScript
 */

import { render } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Dashboard Component
 */
function Dashboard() {
    return (
        <div className="claude-seo-dashboard">
            <h2>{__('Welcome to Claude SEO Pro', 'claude-seo')}</h2>
            <p>{__('AI-powered SEO optimization for WordPress', 'claude-seo')}</p>

            <div className="seo-widget">
                <h3>{__('Getting Started', 'claude-seo')}</h3>
                <ol>
                    <li>{__('Configure your Claude API key in Settings', 'claude-seo')}</li>
                    <li>{__('Edit a post and use the Claude SEO meta box', 'claude-seo')}</li>
                    <li>{__('Analyze your content and get AI-powered suggestions', 'claude-seo')}</li>
                </ol>
            </div>

            <div className="seo-widget">
                <h3>{__('Quick Stats', 'claude-seo')}</h3>
                <p>{__('Dashboard analytics coming soon...', 'claude-seo')}</p>
            </div>
        </div>
    );
}

/**
 * Initialize Dashboard
 */
document.addEventListener('DOMContentLoaded', function() {
    const dashboardRoot = document.getElementById('claude-seo-dashboard-root');

    if (dashboardRoot) {
        render(<Dashboard />, dashboardRoot);
    }

    // Meta box functionality
    initMetaBox();
});

/**
 * Initialize meta box interactions
 */
function initMetaBox() {
    const analyzeButton = document.getElementById('claude-analyze-content');
    const generateButton = document.getElementById('claude-generate-meta');

    if (analyzeButton) {
        analyzeButton.addEventListener('click', analyzeSEO);
    }

    if (generateButton) {
        generateButton.addEventListener('click', generateMetaTags);
    }

    // Character counters
    const titleInput = document.getElementById('claude_seo_title');
    const descInput = document.getElementById('claude_seo_description');

    if (titleInput) {
        titleInput.addEventListener('input', updateCharCount);
    }

    if (descInput) {
        descInput.addEventListener('input', updateCharCount);
    }
}

/**
 * Analyze SEO
 */
function analyzeSEO() {
    const postId = document.getElementById('post_ID').value;
    const resultsDiv = document.getElementById('claude-seo-analysis-results');

    resultsDiv.innerHTML = '<div class="claude-seo-loading"></div>';

    fetch(claudeSeoData.ajaxUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'claude_seo_analyze_content',
            post_id: postId,
            nonce: claudeSeoData.nonce
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayAnalysisResults(data.data);
        } else {
            resultsDiv.innerHTML = '<p class="error">' + data.data.message + '</p>';
        }
    })
    .catch(error => {
        resultsDiv.innerHTML = '<p class="error">Error analyzing content</p>';
    });
}

/**
 * Generate meta tags with AI
 */
function generateMetaTags() {
    const postId = document.getElementById('post_ID').value;

    alert(__('AI meta tag generation coming soon!', 'claude-seo'));
}

/**
 * Display analysis results
 */
function displayAnalysisResults(analysis) {
    const resultsDiv = document.getElementById('claude-seo-analysis-results');

    let html = '<h4>' + __('SEO Analysis Results', 'claude-seo') + '</h4>';
    html += '<p><strong>' + __('SEO Score:', 'claude-seo') + '</strong> ' + analysis.seo_score + '/100</p>';

    if (analysis.issues && analysis.issues.length > 0) {
        html += '<h5>' + __('Issues Found:', 'claude-seo') + '</h5>';
        html += '<ul class="seo-issues">';
        analysis.issues.forEach(issue => {
            html += '<li class="severity-' + issue.severity + '">' + issue.message + '</li>';
        });
        html += '</ul>';
    }

    if (analysis.recommendations && analysis.recommendations.length > 0) {
        html += '<h5>' + __('Recommendations:', 'claude-seo') + '</h5>';
        html += '<ul>';
        analysis.recommendations.forEach(rec => {
            html += '<li>' + rec + '</li>';
        });
        html += '</ul>';
    }

    resultsDiv.innerHTML = html;

    // Update score display
    const scoreDisplay = document.querySelector('.score-number');
    if (scoreDisplay) {
        scoreDisplay.textContent = analysis.seo_score;
        scoreDisplay.parentElement.setAttribute('data-score', analysis.seo_score);
    }
}

/**
 * Update character count
 */
function updateCharCount(event) {
    const input = event.target;
    const counter = input.nextElementSibling;

    if (counter && counter.classList.contains('char-count')) {
        const count = counter.querySelector('.count');
        if (count) {
            count.textContent = input.value.length;
        }
    }
}
