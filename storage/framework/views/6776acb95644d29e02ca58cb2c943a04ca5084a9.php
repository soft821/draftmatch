<!--
  Copyright (c) 2015-present, Facebook, Inc. All rights reserved.

  You are hereby granted a non-exclusive, worldwide, royalty-free license to
  use, copy, modify, and distribute this software in source code or binary
  form for use in connection with the web services and APIs provided by
  Facebook.

  As with any software that integrates with the Facebook platform, your use
  of this software is subject to the Facebook Developer Principles and
  Policies [http://developers.facebook.com/policy/]. This copyright notice
  shall be included in all copies or substantial portions of the software.

  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
  THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
  FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
  DEALINGS IN THE SOFTWARE.
-->
<!DOCTYPE html>
<html>

<head>
  <title>Aggregator BM Onboarding Example</title>
  <meta charset="UTF-8">
  <link rel="stylesheet" href="<?php echo e(asset('css/facebook.css')); ?>">
  <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
</head>

<body>
  <script>
    let access_token;
    let adaccount;
    let bmid;
    // This is called with the results from from FB.getLoginStatus().
    function statusChangeCallback(response) {
      access_token = response.authResponse.accessToken;
      alert(access_token);
      console.log(access_token);
      // The response object is returned with a status field that lets the
      // app know the current login status of the person.
      // Full docs on the response object can be found in the documentation
      // for FB.getLoginStatus().
      if (response.status === "connected") {
        // Logged into your app and Facebook.
        // Automatically create the client BM as user logs in or fetches existing BM and Ad Account
        alert(response.status);
        createClientBM();
        document.getElementById('login').style.display = "none";
      } else {
        // The person is not logged into your app or we are unable to tell.
        document.getElementById("status").innerHTML =
          "Please log " + "into this app.";
      }
    }

    // This function is called when someone finishes with the Login
    // Button.  See the onlogin handler attached to it in the sample
    // code below.
    function checkLoginState() {
      FB.getLoginStatus(function(response) {
        statusChangeCallback(response);
      });
    }

    window.fbAsyncInit = function() {
      FB.init({
        appId: "2045254829123567",
        cookie: true, // enable cookies to allow the server to access
        // the session
        xfbml: true, // parse social plugins on this page
        version: "v3.0" // use graph api version 2.11
      });

      FB.getLoginStatus(function(response) {
        // alert(response.authResponse.accessToken);
        statusChangeCallback(response);
      });
    };

    // Load the SDK asynchronously
    (function(d, s, id) {
      var js,
        fjs = d.getElementsByTagName(s)[0];
      if (d.getElementById(id)) return;
      js = d.createElement(s);
      js.id = id;
      js.src = "https://connect.facebook.net/en_US/sdk.js";
      fjs.parentNode.insertBefore(js, fjs);
    })(document, "script", "facebook-jssdk");

    function createClientBM() {
      var xhttp = new XMLHttpRequest();
      xhttp.onreadystatechange = function() {
        if (this.readyState === 4 && this.status === 200) {
          let response = this.responseText.replace(/"/g, "").split(",");
          if(!bmid && !adaccount) {
            bmid = response[0];
            adaccount = response[1];
            document.getElementById("status").innerHTML =
              "BM ID: " +
              response[0] +
              ", Ad Account ID: " +
              response[1];
            document.getElementById('loader').style.display = "none";
            document.getElementById('content').style.display = 'block';
          }
        }
      };
      var uri = 'facebookClient';
      var params = 'access_token='+access_token;
      xhttp.open(
        "GET",
         uri,
         true
      );
      xhttp.send(params);
      document.getElementById('loader').style.display = "block";
    }

    function createClickToMessengerAd() {
      var ad_message = document.getElementById('ad-message').value;
      var page_welcome_message = document.getElementById('page-welcome-message').value;
      var xhttp = new XMLHttpRequest();
      xhttp.onreadystatechange = function() {
        if (this.readyState === 4 && this.status === 200) {
          document.getElementById("status").innerHTML = this.responseText;
        }
      };
      xhttp.open(
        "GET",
        window.location.href + "/api.php?action=create_click_to_messenger_ad" +
        "&adaccount=" +
        adaccount +
        "&bmid=" +
        bmid +
        '&ad_message=' +
        ad_message +
        '&page_welcome_message=' +
        page_welcome_message,
        true
      );
      xhttp.send();
      document.getElementById("status").innerHTML = "Creating ad...";
    }
  </script>

  <div class="container w3-display-middle">
    <div class="card">
      <header class="w3-container w3-blue">
        <h3>Create Ads</h3>
      </header>
      <div class="insidecontainer">
        <div id="loader" class="loader" style="display:none"></div>
        <div id="login" class="fb-login-button" onlogin="checkLoginState();" scope="ads_management,business_management" data-max-rows="1" data-size="large" data-button-type="login_with" data-show-faces="false" data-auto-logout-link="true" data-use-continue-as="false"></div>
        <div id="content" class="content" style="display:none">
          <h1 class="content-header">Create a Click-to-Messenger Ad</h1>
          <div class="form">
            <div class="form-group">
              <label for="ad-message">Ad Message</label>
              <input type="text" name="ad-message" id="ad-message">
            </div>
            <div class="form-group">
              <label for="page-welcome-message">Welcome Message</label>
              <input type="text" name="page-welcome-message" id="page-welcome-message">
            </div>
            <button class="create-ad-button" onclick="createClickToMessengerAd()">Create Ad</button>
          </div>
        </div>
        <div id="status">
        </div>
      </div>
    </div>
  </div>
</body>

</html>
