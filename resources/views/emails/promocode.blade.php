<html>
    <head>
        <style type="text/css">
            .coupon {
                border: 5px dotted #bbb; 
                width: 80%; 
                border-radius: 15px; 
                margin: 0 auto; 
                max-width: 600px; 
            }
            
            .container {
                padding: 2px 16px;
                background-color: #f1f1f1;
            }
            
            .promo {
                background: #ccc;
                padding: 3px;
                font-weight:bold;
            }
            
            .expire {
                color: red;
            }
        </style>
    </head>
    
    <body>

        <div class="coupon">
          <div class="container">
            <h3>DRAFTMATCH</h3>
          </div>
          <img src="{{$message->embed($logoPath)}}" alt="Avatar" style="width:100%;">
          <div class="container" style="background-color:white">
            <h2><b>Thank you for your being our Customer.</b></h2> 
            <p><a href="www.draftmatch.com" style="color:blue;">www.draftmatch.com</a></p>
          </div>
          <div class="container">
            <p>Use Promo Code: <span class="promo">{{$promocode}}</span></p>
            <p class="expire">Expires: {{$expire}}</p>
          </div>
        </div>
    </body>
</html>