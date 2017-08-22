# Gmail Contact Extractor

### What is this for?
It's a tool that, given a Gmail query, will progressively fetch all messages from all 
matched threads, and extract the names and e-mails from the senders.

It will handle the Google API throttling by batching and grouping requests.

### How to use
After cloning the repository locally, enter the [Google Developer Console](https://console.developers.google.com) 
and create a new client. Enable the GMail API and create Web (OAuth) credentials for logging in.

Once you have the credentials, input your client ID and secret in the `.env` file. 
Make sure to add `http://127.0.0.1:5000` and `http://127.0.0.1:5000/` to the list of authorized redirects.

Install [Composer](https://getcomposer.org/) dependencies by running `composer install`.

Then, start the application by running `php -S 127.0.0.1:5000` while at the root of the dir.

Navigate to the [http://127.0.0.1:5000](http://127.0.0.1:5000) URL, click the login button, login with the desired
GMail account, and input the search query and max results you wish to fetch.

### Requirements

- PHP 7.1
- Composer


### License

MIT

