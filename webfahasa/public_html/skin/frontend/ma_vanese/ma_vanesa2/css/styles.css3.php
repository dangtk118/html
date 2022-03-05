<?php
    header('Content-type: text/css; charset: UTF-8');
    header('Cache-Control: must-revalidate');
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');
    $url = $_REQUEST['url'];
?>

@-webkit-keyframes moveFromTop {
    from {
        -webkit-transform: translateY(-100%);
    }
    to {
        -webkit-transform: translateY(0%);
    }
}
@-moz-keyframes moveFromTop {
    from {
        -moz-transform: translateY(-100%);
    }
    to {
        -moz-transform: translateY(0%);
    }
}
@-ms-keyframes moveFromTop {
    from {
        -ms-transform: translateY(-100%);
    }
    to {
        -ms-transform: translateY(0%);
    }
}

@-webkit-keyframes smallToBig{
    from {
        -webkit-transform: scale(0.1);
    }
    to {
        -webkit-transform: scale(1);
    }
}
@-moz-keyframes smallToBig{
    from {
        -moz-transform: scale(0.1);
    }
    to {
        -moz-transform: scale(1);
    }
}
@-ms-keyframes smallToBig{
    from {
        -ms-transform: scale(0.1);
    }
    to {
        -ms-transform: scale(1);
    }
}

@keyframes rotate{
    0% { transform: scale(1) rotate(0);}
    50% { transform: scale(0.5) rotate(180deg);}
    100% { transform: scale(1) rotate(360deg);}
}
@-webkit-keyframes rotate{
    0% { -webkit-transform: scale(1) rotate(0);}
    50% { -webkit-transform: scale(0.5) rotate(180deg);}
    100% { -webkit-transform: scale(1) rotate(360deg);}
}
@-moz-keyframes rotate{
    0% { -moz-transform: scale(1) rotate(0);}
    50% { -moz-transform: scale(0.5) rotate(180deg);}
    100% { -moz-transform: scale(1) rotate(360deg);}
}

ul.link-follow li a span
{
-ms-filter: "progid: DXImageTransform.Microsoft.Alpha(Opacity=0)";
filter: alpha(opacity=0);
opacity: 0;	
}

ul.link-follow li a:hover span
{
-ms-filter: "progid: DXImageTransform.Microsoft.Alpha(Opacity=100)";
filter: alpha(opacity=100);
opacity: 100;
}

{
-moz-border-radius:4px;
-webkit-border-radius:4px;
border-radius:4px;
}


{
-moz-border-radius:5px;
-webkit-border-radius:5px;
border-radius:5px;
}
ul.link-follow a,
ul.link-follow li a span,
.box-title,
.footer-static-content ul li a,
.pt_vmegamenu div.pt_menu .parentMenu a, 
.pt_vmegamenu div.pt_menu .parentMenu span.block-title,
.pull-left,
.link a,
.header .form-search button.button span,
.box-content .box-title a.view,
.header .form-search .dropdown-toggle,
.flex-direction-nav a,
 .bx-wrapper .bx-controls a
{
transition: 0.2s ease-in-out;
-moz-transition: 0.2s ease-in-out;
-webkit-transition: 0.2s ease-in-out;
-o-transition: 0.2s ease-in-out;
-ms-transition: 0.2s ease-in-out
}
.sub-menu-inner img:hover
{
-ms-filter: "progid: DXImageTransform.Microsoft.Alpha(Opacity=80)";
filter: alpha(opacity=80);
opacity: 0.8;
}

{
-webkit-animation: smallToBig 300ms ease;
-moz-animation: smallToBig 300ms ease;
-ms-animation: smallToBig 300ms ease;
}

