# Develop an Custom Enquiry Form Plugin for WordPress

## Objective
Develop an Enquiry Form Plugin for WordPress

For an assessment, you are required to develop a custom WordPress enquiry form plugin.
- Form can be used anywhere on frontend (website) using short code.
- The design of the form should be mobile responsive and should work on any theme.
- CSS of the form should only be called when form is loaded on any page using the short code.
- CSS should be in .css file (avoid using in-line styling).
- Form fields: Full name, email address, phone and message.
- Form submission should be done using AJAX and result / message should be shown in the
bottom area of the form.
- JavaScript / jQuery should only be called when form is loaded on any page using the short
code.
- JavaScript / jQuery should be in .js file (do not use in-line &lt;script&gt; tags).
- When form is submitted, validate email address and all other fields are also mandatory. If
any field is empty, show error message “All fields are required” in the bottom area of the
form.
- Use Google reCAPTCHA to avoid spamming
- For WordPress Admin, settings page is required to set the following:
o Option to use WordPress mail function or SMTP function (Mail: SMTP / WP Mail)
o SMTP settings (email, from, password, host, port etc)
o If WP Mail option is selected, send email using Admin email (Settings -&gt; General)
o Send enquiry to (TO email address)
o Subject
o Message (use [NAME], [EMAIL], [PHONE] and [TEXT] as text and should be replaced
with the form data accordingly)
o If SMTP option is selected, send email using SMTP server
o TEXTAREA to set message when email is sent successfully

## Prerequisites
- A Wordpress website for wordpress Plugin
- Google reCaptcha API for recaptcha
- SMTP Mail Server
- WP Mail Server
- PHP Programming language


### Custom Plugin Development Plan
## Step 1: Plugin Structure
Create the following directory structure for the plugin:
```bash
inzone-enquiry-form/
│── assets/
│   ├── css/
│   │   ├── inzone-enquiry-form.css
│   ├── js/
│   │   ├── inzone-enquiry-form.js
│── inzone-enquiry-form.php

```  
## Step 2: Core Plugin File (inzone-enquiry-form.php)
1. Define the plugin metadata.
2. Register activation/deactivation hooks.
3. Load required scripts and styles when shortcode is present.
4. Register the admin settings page.

## Step 3: Enquiry Form Shortcode

1. Use add_shortcode( 'enquiry_form', array( $this, 'render_enquiry_form' ) ); to create a shortcode.
2. Design a form with Full Name, Email, Phone, Message, and Google reCAPTCHA.
3. Include JavaScript and CSS only when the form is present.

## Step 4: AJAX Form Submission

1. Use wp_ajax_nopriv_submit_enquiry for non-logged-in users.
2. Validate fields and ensure email format is correct.
3. Implement Google reCAPTCHA verification.
4. Send email via WP Mail or SMTP based on the admin settings.

## Step 5: Admin Settings Page
Provide options for:

1. Mail Sending Method: WP Mail / SMTP.
2. SMTP Settings: Email, password, host, port.
3. Recipient Email (TO) Address.
4. Email Subject and Message Template.
5. Custom Success Message.
