# interview_wp
WordPress plugin as an interview exercise machine. Add questions/answer to the post and paste the shortcode to the page/post to get an exercise machine for preparing for interviews.  

Shortcode for any page  
[interview_test topic="project-management" timer="5" random="1"]  

Entering without shortcode  
http://iww.loc/full-screen-quiz/?topic=project-management&timer=20&random=1  

Library page can be entered like this  
[http://iww.loc/interview-library/?topic=php]

Also there is an importer in the admin

Enter each post separated by ===QUESTION===.
First line = question/title (meta), remaining lines = answer/content.
Supports HTML, Markdown, lists, code blocks, etc.

Example:
===QUESTION===
What is PHP?
PHP is a server-side scripting language used to build dynamic websites.