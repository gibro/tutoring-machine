<?php
// English language strings for the Tutoring Machine block.
$string['pluginname']         = 'Tutoring Machine';
$string['tutoring_machine:addinstance'] = 'Add a new Tutoring Machine block';
$string['tutoring_machine:myaddinstance'] = 'Add a new Tutoring Machine block to Dashboard';
$string['configtitle'] = 'Block title';
$string['configtitle_help'] = 'Override the default Tutoring Machine name for this block instance. Leave empty to keep the plugin default.';
$string['configassistantname'] = 'Assistant display name';
$string['configassistantname_help'] = 'Set a custom assistant name shown in all Tutoring Machine replies for this block. Leave empty to reuse the global assistant name.';
$string['configincludecontext'] = 'Share course context';
$string['configincludecontext_help'] = 'When enabled, the Tutoring Machine sends selected course materials together with each request so the assistant can ground its answers in the Moodle course. Leave disabled to keep all course content on your Moodle server.';
$string['configincludecontext_option'] = 'Include course materials in the context sent to the AI';
$string['context_links_enable'] = 'Include external links';
$string['context_links'] = 'External context links';
$string['context_links_help'] = 'Provide one HTTPS URL per line. Only domains whitelisted in the global settings are fetched. The cleaned text is added to the tutoring context when context sharing is enabled.';
$string['configwelcomemessage'] = 'Welcome message';
$string['configwelcomemessage_help'] = 'Define the first message learners see when they open the chat. Use it to introduce the assistant, reference the system prompt, or add transparency notes.';
$string['configwelcomemessageanalytics'] = 'Welcome message with analytics';
$string['configwelcomemessageanalytics_help'] = 'Optional alternative welcome message shown when teaching analytics are enabled. Leave empty to reuse the standard welcome message.';
$string['assistantname']      = 'Assistantname';
$string['assistantnamedesc']  = 'Enter the name that is displayed in the chat as Assistant.';
$string['courselogo']         = 'Course-specific Logo';
$string['courselogo_help']    = 'Upload a custom logo for this course instance. If not set, the default logo will be used.';
// API Keys
$string['openai_apikey']      = 'OpenAI API Key';
$string['openai_apikeydesc']  = 'Enter your OpenAI API key for accessing OpenAI models (GPT-5, GPT-5 Mini, GPT-5 Nano, etc.).';
$string['google_apikey']      = 'Google API Key';
$string['google_apikeydesc']  = 'Enter your Google API key for accessing Gemini models.';
// Anthropic API settings removed

// Legacy - for backward compatibility
$string['apikey']             = 'API Key (Legacy)';
$string['apikeydesc']         = 'Enter your API key for the Tutoring Machine functionality (deprecated, use provider-specific keys instead).';
$string['metaprompt']         = 'Tutoring Machine Instructions';
$string['metaprompt_help']    = 'Enter special instructions for the Tutoring Machine in this course. These instructions will be added before every user message. For example: "Always be friendly and use simple language." or "You are a Moodle support assistant, always use informal language."';
$string['course_content_context'] = 'Course Content Context';
$string['course_content_context_desc'] = 'The Tutoring Machine uses content from the course (text pages and glossaries) as context for its responses.';

// Appearance settings
$string['appearance'] = 'Appearance';
$string['main_color'] = 'Main color';
$string['main_color_help'] = 'Select the main color for the Tutoring Machine interface. This color will be used for the header, buttons, and user message bubbles.';

// Context sources settings
$string['contextsources'] = 'Context Sources';
$string['use_textpages'] = 'Include Text Pages';
$string['use_textpages_desc'] = 'Use content from course text pages as context for responses.';
$string['use_textpages_help'] = 'When enabled, the content of all text pages in the course will be used to answer questions.';
$string['use_glossaries'] = 'Include Glossaries';
$string['use_glossaries_desc'] = 'Use content from course glossaries as context for responses.';
$string['use_glossaries_help'] = 'When enabled, the entries from all glossaries in the course will be used to answer questions.';
$string['use_internet'] = 'Allow Internet Search';
$string['use_internet_desc'] = 'Allow the Tutoring Machine to use information from the internet when an answer is not found in the course content.';
$string['use_internet_help'] = 'When enabled, the Tutoring Machine can draw on information from the internet to answer questions not covered by the course content. This information will be combined with the course content.';
$string['use_h5p'] = 'Include H5P Activities';
$string['use_h5p_desc'] = 'Use content from H5P activities as context for responses.';
$string['use_h5p_help'] = 'When enabled, the content from all H5P activities in the course will be used to answer questions.';
$string['use_pdfs'] = 'Include PDF Documents';
$string['use_pdfs_desc'] = 'Use content from PDF documents as context for responses.';
$string['use_pdfs_help'] = 'When enabled, the text from all PDF documents in the course will be extracted and used to answer questions.';

$string['use_office'] = 'Include Office Documents';
$string['use_office_desc'] = 'Use content from Word, Excel and PowerPoint documents as context for responses.';
$string['use_office_help'] = 'When enabled, the text from all Microsoft Office documents (Word, Excel, PowerPoint) in the course will be extracted and used to answer questions.';

$string['use_forums'] = 'Include Forums';
$string['use_forums_desc'] = 'Use content from forum discussions as context for responses.';
$string['use_forums_help'] = 'When enabled, the discussions from all forums in the course will be used to answer questions.';

$string['use_quizzes'] = 'Include Quizzes';
$string['use_quizzes_desc'] = 'Use content from quiz questions as context for responses.';
$string['use_quizzes_help'] = 'When enabled, the questions and answers from all quizzes in the course will be used to answer questions.';

$string['use_books'] = 'Include Books';
$string['use_books_desc'] = 'Use content from book activities as context for responses.';
$string['use_books_help'] = 'When enabled, the chapters from all books in the course will be used to answer questions.';

$string['use_assignments'] = 'Include Assignments';
$string['use_assignments_desc'] = 'Use content from assignments as context for responses.';
$string['use_assignments_help'] = 'When enabled, the descriptions of all assignments in the course will be used to answer questions.';

$string['use_labels'] = 'Include Labels';
$string['use_labels_desc'] = 'Use content from label resources as context for responses.';
$string['use_labels_help'] = 'When enabled, the content from all labels in the course will be used to answer questions.';

$string['use_urls'] = 'Include URL Resources';
$string['use_urls_desc'] = 'Use content from URL resources as context for responses.';
$string['use_urls_help'] = 'When enabled, the descriptions and URLs of all URL resources in the course will be used to answer questions.';

$string['use_lessons'] = 'Include Lessons';
$string['use_lessons_desc'] = 'Use content from lesson activities as context for responses.';
$string['use_lessons_help'] = 'When enabled, the content from all lessons in the course will be used to answer questions.';

// Admin settings groups
$string['basicsettings'] = 'Basic Settings';
$string['basicsettings_desc'] = 'Configure the basic settings for the Tutoring Machine block. To test the API connection, you can use the <a href="{$CFG->wwwroot}/blocks/tutoring_machine/api_test_web.php" target="_blank">API Test Tool</a> which verifies the configured API keys.';
$string['linkdomain_whitelist'] = 'Link domain whitelist';
$string['linkdomain_whitelist_desc'] = 'Only URLs pointing to these domains will be fetched and processed (one domain per line). Subdomains are allowed automatically.';
$string['respect_robots'] = 'Respect robots.txt';
$string['respect_robots_desc'] = 'When enabled, the Tutoring Machine checks robots.txt before retrieving external pages and skips URLs that disallow the configured user agent.';
$string['link_useragent'] = 'Link fetcher user agent';
$string['link_useragent_desc'] = 'HTTP user agent header used when the Tutoring Machine fetches external links.';
$string['link_refresh_ttl'] = 'Link refresh interval (seconds)';
$string['link_refresh_ttl_desc'] = 'Controls how long cached link contents stay valid before being refetched. Leave empty to use the default (24 hours).';
$string['default_include_context'] = 'Share course context by default';
$string['default_include_context_desc'] = 'Controls whether new block instances include Moodle course materials in AI requests. Editors can override this per course.';
$string['default_welcome_message'] = 'Default welcome message';
$string['default_welcome_message_desc'] = 'Prefills the welcome message for new block instances. Learners see this text when they open the chat unless the course overrides it.';
$string['default_welcome_message_value'] = 'Hi there! I am your learning companion. Ask whenever you need help!';
$string['default_welcome_message_analytics'] = 'Default welcome message (analytics enabled)';
$string['default_welcome_message_analytics_desc'] = 'Prefills the alternative welcome message that appears when teaching analytics are enabled. Leave empty to reuse the default welcome message.';
$string['default_welcome_message_analytics_value'] = 'Hi there! I am your learning companion. Your teachers activated anonymous chat analytics to learn which topics need more support. Feel free to ask anything - no question is too small!';
$string['modelsettings'] = 'AI Model Settings';
$string['modelsettingsdesc'] = 'Choose the AI model to be used by default for all Tutoring Machine instances. Models differ in quality, speed, and cost.';
$string['model_usage_info'] = 'Application Recommendations';
$string['model_usage_info_desc'] = 'Model recommendations for different learning scenarios:<ul>
<li><strong>For tutoring and complex explanations:</strong> GPT-5 (flagship reasoning)</li>
<li><strong>For agentic workflows and coding:</strong> GPT-5 with medium or high reasoning effort</li>
<li><strong>For everyday chat at lower cost:</strong> GPT-5 Mini (balanced speed and price)</li>
<li><strong>For latency-critical or batch classification tasks:</strong> GPT-5 Nano (fastest turnaround)</li>
</ul>';
$string['parametersettings'] = 'Parameter Settings';
$string['parametersettingsdesc'] = 'Configure the parameters for AI response generation to adjust quality and style of responses.';
$string['systemsettings'] = 'System Settings';
$string['systemsettings_desc'] = 'Advanced settings for API communication. If you experience connection issues, you can use the <a href="{$CFG->wwwroot}/blocks/tutoring_machine/api_test_web.php" target="_blank">API Test Tool</a> to diagnose communication with the AI providers.';

// Provider settings
$string['default_provider'] = 'Default AI Provider';
$string['default_provider_desc'] = 'Choose the AI provider to be used for all Tutoring Machine instances unless configured otherwise.';
$string['provider_openai'] = 'OpenAI (GPT-5)';
$string['provider_google'] = 'Google (Gemini)';
// Anthropic provider removed

// Model settings
$string['default_model'] = 'Default AI Model';
$string['default_model_desc'] = 'Choose the AI model to be used for all Tutoring Machine instances unless configured otherwise.';
$string['ai_model'] = 'AI Model for this Course';
$string['ai_model_help'] = 'Choose the AI model to be used for this Tutoring Machine. If you use the system default, the option set in the global settings will be used.';
$string['use_system_default'] = 'Use System Default';
$string['usage_intent'] = 'Usage Purpose';
$string['usage_intent_help'] = 'Choose the primary purpose of the Tutoring Machine in this course. This helps in selecting the optimal AI model.';
$string['usage_intent_tutor'] = 'Tutor & Complex Explanations';
$string['usage_intent_research'] = 'Research Help & Source Reference';
$string['usage_intent_summarization'] = 'Summaries & Concept Explanations';
$string['usage_intent_qa'] = 'Simple Questions & Answers';
$string['usage_intent_creative'] = 'Creative Learning & Idea Generation';
$string['model_recommendations'] = 'Model Recommendations by Usage Purpose';

// Parameter settings
$string['temperature'] = 'Creativity (Temperature)';
$string['temperature_desc'] = 'Controls how creative and diverse the Tutoring Machine\'s responses are. Lower values (0.1-0.5) produce more consistent, fact-oriented responses, while higher values (0.6-1.0) produce more creative and diverse responses.';
$string['top_p'] = 'Response Diversity (Top-P)';
$string['top_p_desc'] = 'Controls the variability of word choice - unlike temperature which controls general creativity. Top-P limits selection to the most probable tokens, determining the "focus" of the response. A higher value (0.9-1.0) allows more varied phrasing, while a lower value (0.1-0.5) concentrates answers on more predictable formulations.';

$string['response_format'] = 'Response Format';
$string['response_format_desc'] = 'Specifies the format in which the AI model\'s responses should be returned. "Text" is the standard format for natural language responses. "JSON" enforces structured output according to JSON schema, which is particularly useful when responses need to be processed programmatically. With JSON format, answers are always delivered in valid JSON objects, increasing processing reliability but potentially limiting the naturalness of the text.';
$string['response_format_text'] = 'Text (natural language responses)';
$string['response_format_json'] = 'JSON (structured, machine-readable responses)';
$string['max_tokens'] = 'Maximum Token Count';
$string['max_tokens_desc'] = 'The maximum number of tokens (word parts) that the model can generate for a response. Higher values allow longer responses but increase cost and response time.';
$string['timeout'] = 'Time Limit (Seconds)';
$string['timeout_desc'] = 'The maximum time in seconds to wait for a response from the AI service before the request times out.';

// Error messages
$string['noapikey'] = 'API key not configured. Please configure the API key in the Tutoring Machine block settings.';
$string['apiconnectionerror'] = 'Error connecting to the API. Please try again later.';
$string['toomanyrequests'] = 'Too many requests. Please try again later.';
$string['invalidcourseid'] = 'Invalid course ID.';
$string['usernotincourse'] = 'You are not enrolled in this course.';
$string['invalidblockid'] = 'Invalid block ID.';
$string['blocktypenottutoringmachine'] = 'Block is not a Tutoring Machine block.';
$string['accessdenied'] = 'Access denied.';
$string['nopermission'] = 'You do not have permission to access this resource.';
$string['missingparam'] = 'Missing required parameter: {$a}.';
$string['invalidparam'] = 'Invalid parameter: {$a}.';
$string['internalerror'] = 'An internal error occurred.';
$string['csrfcheck'] = 'CSRF check failed.';
$string['messagerequired'] = 'Message cannot be empty.';

// Teaching Analytics
$string['teachinganalytics'] = 'Teaching Analytics';
$string['enable_analytics'] = 'Enable Teaching Analytics';
$string['enable_analytics_desc'] = 'Collects anonymized user inputs to analyze the most frequently asked questions. Data is stored without user identification.';
$string['enable_analytics_help'] = 'When enabled, learners\' questions are stored anonymously to provide teachers with insights into frequently asked questions. No personal data is collected.';
$string['analytics_notice_title'] = 'Privacy Notice';
$string['analytics_notice'] = '<strong>Important:</strong> When you enable teaching analytics, learners will be shown a notice during their first use of the Tutoring Machine that their requests are being stored anonymously for analysis purposes.';
$string['analytics_retention'] = 'Data retention period';
$string['analytics_retention_help'] = 'Defines how long anonymized requests are stored before being automatically deleted.';
$string['retention_1week'] = '1 week';
$string['retention_1month'] = '1 month';
$string['retention_3months'] = '3 months';
$string['retention_6months'] = '6 months';
$string['retention_1year'] = '1 year';

$string['analytics_dashboard'] = 'Open Analytics Dashboard';
$string['analytics_dashboard_desc'] = 'Shows statistics about the most frequently asked questions in the course.';
$string['no_analytics_data'] = 'No analytics data available. Enable teaching analytics and wait for learners to use the Tutoring Machine.';
$string['most_common_questions'] = 'Most Common Questions';
$string['query_count'] = 'Count';
$string['total_queries'] = 'Total number of queries';
$string['queries_last_days'] = 'Queries from the last {$a} days';
$string['analytics_timeperiod'] = 'Time period';
$string['analytics_not_enabled'] = 'Teaching analytics are not enabled for this Tutoring Machine. Please enable analytics in the block settings.';
$string['query'] = 'Query';
$string['query_types'] = 'Query types';
$string['queries'] = 'Queries';
$string['querytype_content'] = 'Content questions';
$string['querytype_assignment'] = 'Assignment questions';
$string['querytype_exam'] = 'Exam questions';
$string['querytype_grade'] = 'Grade questions';
$string['querytype_technical'] = 'Technical questions';
$string['querytype_schedule'] = 'Schedule questions';
$string['data_anonymized_notice'] = 'All data is anonymized. No conclusions about individual users are possible.';
$string['analytics_info'] = 'Teaching Analytics displays anonymized requests from learners to the Tutoring Machine. This information can help identify common questions and adjust teaching materials accordingly.';
$string['data_collection_notice'] = 'Note: Your queries are stored anonymously for analytics purposes. No personal data is collected.';

// Task strings
$string['task_cleanup_analytics'] = 'Clean up old Tutoring Machine analytics data';
$string['task_refresh_links'] = 'Refresh external link cache';

// Prompt Suggestions
$string['prompt_suggestions'] = 'Prompt Suggestions';
$string['prompt_suggestions_desc'] = 'Provide suggestions for effective prompts that learners can use with the Tutoring Machine.';
$string['prompt_suggestions_help'] = 'Enter one suggestion per line. These will be displayed as clickable options in the chat interface.';
$string['enable_prompt_suggestions'] = 'Enable Prompt Suggestions';
$string['enable_prompt_suggestions_desc'] = 'Show a button in the chat interface that allows learners to select from predefined prompt suggestions.';
$string['prompt_suggestions_button_text'] = 'Suggestions';
$string['prompt_suggestions_placeholder'] = 'E.g.:
Explain the concept of...
Compare and contrast...
Summarize the key points about...
Help me understand...';
