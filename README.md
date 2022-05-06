# Send Email using GMail APIs - OAuth

## Setup Google Developer Console Project:

### Step 1: Go to Google Developers Console (https://console.cloud.google.com/). 
### Step 2: Choose 'Select a project' and make a new one. Name it and hit the 'Create' button.
### Step 3: On the left bar, choose 'Library' and move to the API Library page. Find Gmail API and click on it. Enable the API for the chosen project.
### Step 4: Once the API is enabled, you will be taken to a Credentials dashboard. There, select the 'OAuth Client ID' from the Create Credentials dropdown list. 
### Step 5: Download OAuth Client credential JSON file and save it to your project directory.
### Step 6: Then you’ll see the 'Configure consent' button. By clicking, you'll get to the page where you can simply enter the name of your application and specify the authorized domains. Please fill in the other fields if you wish.
### Step 6: Click 'Save' and choose your app type (Web App, Android, Chrome App, iOS, or other). After that, name your OAuth Client ID. Also, enter JavaScript sources and redirect domains to use with requests from the browser or web server. Click 'Create' to complete.


## Setup Google Client Library for PHP

### Run below command
> composer require google/apiclient:"^2.0"

## Get Authorization from Gmail

### Run below command to generate token.json
> php index.php


To generate token.json file, run this file in your Commad Line. i.e. php index.php
It will generate one link which need to be run in browser.
After authenticating to your Google App, it will redirect to specified redirect URL.
Copy "code" parameter value from url and paste it to commad line. i.e. Enter Verification Code: XXXX
Now check token.json in you directory. 
Comment below IF block once successfully token generated.

```
if (php_sapi_name() != 'cli') {
    throw new Exception('This application must be run on the command line.');
}
```
