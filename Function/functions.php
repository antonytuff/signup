<?php
//Clear Unwanted Input

function clean($string) {

    return htmlentities($string);
}


//Redirect users to another Location

function redirect($location){

    return header("Location: {$location}");
}

//Setting echo and sessions management to alert users

function  set_message($message){
    if(!empty($message)){

        $_SESSION['message'] =$message;

    }else{
        $message="";
    }
}


//Display the echo messages
function display_message(){
    if (isset($_SESSION['message'])){
        echo $_SESSION['message'];
        unset($_SESSION['message']);
        }
}


//Make our Forms Secure
function token_generator(){
    $token = $_SESSION['token'] =md5(uniqid(mt_rand(), true));
    return $token;

}


//Display Validation Errors
function validation_errors($error_message){

$error_message= <<<DELIMETER
                
                <div class="alert alert-danger alert-dismissible" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span></button>
                <strong>Warning!</strong> $error_message
                </div> 
                
DELIMETER;
return $error_message; 
}


//Email Exists
function email_exists($email){
    $sql =" SELECT id FROM users WHERE email='$email'";

    $result =query($sql);

    if(row_count($result) ==1){

        return true;
    }else{

        return false;
    }
}


//Checks if the Username exists 
function username_exists($username){

    $sql= "SELECT id FROM users WHERE username='$username'";

    $results=query($sql);

    if(row_count($results) ==1){

        return true;
    }else{

        return false;
    }
}


/***Send Activation Link to User */
function send_email($email,$subject,$msg,$header){

    return mail($email,$subject,$msg,$header);


}



/***********************Validation Function***************** */

function validate_user_registration(){

    $errors=[];

    $min=3;
    $max=20;



    if($_SERVER['REQUEST_METHOD'] == "POST"){


       $first_name          = clean($_POST['first_name']);
       $last_name           = clean($_POST['last_name']);
       $username            = clean($_POST['username']);
       $email               = clean($_POST['email']);
       $password            = clean($_POST['password']);
       $confirm_password    = clean($_POST['confirm_password']);



       if(strlen($first_name)< $min){

           $errors[] = "Your First Name Cannot be less than {$min} Characters";
           echo '<br>';
       }

       if(strlen($last_name)< $min){

        $errors[] = "Your Last Name Cannot be less than {$min} Characters";
        echo '<br>';
    }

       if(strlen($username)> $max){
           $errors[]= "Username cannot be more than {$max} Characters";
           echo '<br>';
       }

       if(username_exists($username)){

        $errors[]= "Sorry that username is already is taken";
       }


       if(email_exists($email)){

        $errors[]= "Sorry that email already is registered";
       }

       if(strlen($email)> $max){
        $errors[]= "Email cannot be more than {$max} Characters";
        echo '<br>';
    }


    if($password !== $confirm_password){
        $errors[]= "Your Password Fields Do not Macth";
    }


        if(!empty($errors)){
            foreach($errors as $error){

///ERROR DISPLAY
                echo validation_errors($error);
            }        

       } else {

        if(register_user($first_name,$last_name,$username,$email,$password)){

            set_message("<p class='bg-success text-center'>Please check your email for an activation link</p>");
            redirect("index.php");

            //echo "User has Succesfully beeen Registerd";
        }else{
            set_message("<p class='bg-danger text-center'>Sorry, We could not Register the User</p>");
            redirect("index.php");

        }

       }


    }//post Requests
}

//Functions For Registering

function register_user($first_name,$last_name,$username,$email,$password){


        //Escaping Prevents SQL Injection

       $first_name          = escape($first_name);
       $last_name           = escape($last_name);
       $username            = escape($username);
       $email               = escape($email);
       $password            = escape($password);
       

    if(email_exists($email)){

        return false;

    }else if(username_exists($username)){
        return false;

    }else{

        $password =md5($password);

        $validation_code =md5($username + microtime());

        $sql = "INSERT INTO users(first_name,last_name,username,email,password,validation_code, active)";
        $sql.= " VALUES ('$first_name', '$last_name', '$username','$email','$password','$validation_code',0)";

        $result =query($sql);
        confirm($result);


        $subject="Activate Account";
        $msg="Please Click the link below to activate your Account
        http://localhost/login/activate.php?email=$email$code=$validation_code
        ";
        $header="From: noreply@hacker.com";

        send_email($email,$subject,$msg,$header);

       return true;

    }

}
/*****************ACTIVE USER************ */

function activate_user(){

    if($_SERVER['REQUEST_METHOD'] == "GET"){

        if(isset($_GET['email'])){
            
            echo $email = clean($_GET['email']);

            echo $validation_code = clean($_GET['code']);

            $sql = "SELECT id FROM users WHERE email= '".escape($_GET['email'])."' AND validation_code='".escape($_GET['code'])."' ";
            $result =query($sql);
            confirm($result);

            if(row_count($result) == 1){
                $sql2= "UPDATE users SET active =1, validation_code= 0 WHERE email='".escape($email)."' AND validation_code='".escape($validation_code)."' ";
                $result2 =query($sql2);
                confirm($result2);
                set_message("<p class='bg-success'>Your Account has been activated please login</p>");
                redirect("login.php");

            }else{
                set_message("<p class='bg-danger'>Sorry, Your Account could not be activated</p>");
                redirect("login.php");
            }

           

        }
    }
}


/**********************   Login Validation ****************************/

function validate_user_login(){

    $errors=[];

    $min=3;
    $max=20;



    if($_SERVER['REQUEST_METHOD'] == "POST"){

        $email               = clean($_POST['email']);
        $password            = clean($_POST['password']);
        $remember            = isset($_POST['remember']);

        if(empty($email)){

            $errors[]="Email Field cannot be empty";
        }
        if(empty($password)){
            $errors[]="Password Field cannot be empty";
        }




        if(!empty($errors)){

            foreach ($errors as $error){

                echo validation_errors($error);
            }
        }else{

            if(login_user($email, $password,$remember)){

                redirect("admin.php");
            }else{

                echo validation_errors("Your credentials are not correct");
            }
        }

       

    }
}


/*********************************User Login Functions***************************************** */

function login_user($email,$password,$remember){
     
    $sql= "SELECT password,id FROM users WHERE email= '".escape($email)."' AND active=1";

    $result=query($sql);

    if(row_count($result) ==1){

        $row=fetch_array($result);

        $db_password =$row['password'];

        if(md5($password) == $db_password){

            if($remember == "on"){

                setcookie('email', $email, time() +86400);
            }




            $_SESSION['email'] =$email;

            return true;
        } else{

            return false;
        }


        return true;

    }else{

        return false;
    }
}


//uses a Session to Login In us all the time

function logged_in(){

    if(isset($_SESSION['email']) || isset($_COOKIE['email'])){

        return true;

    }else{

        return false;
    }
}


/*************************RECOVER PASSWORD **********************/

function recover_password(){


    if($_SERVER['REQUEST_METHOD'] == "POST"){

        if(isset($_SESSION['token']) && $_POST['token'] == $_SESSION['token']){

            $email =clean($_POST['email']);


           if(email_exists($email)){

            $validation_code =md5($email + microtime());

            setcookie('tmp_access_code',$validation_code, time()+ 60);

            $sql = "UPDATE users SET validation_code = '".escape($validation_code)."' WHERE email='".escape($email)."'";
            $result =query($sql);
            confirm($result);

            $subject = "please reset your password";
            $message = "Here is your reset code {$validation_code}
            
            Click here to reset your password http://localhost/code.php?email=$email&code=$validation_code
            ";

            $headers = "From: noreply@yourwebsite.com";


            if(!send_email($email,$subject,$message, $headers)){



                echo validation_errors("Email could not be sent");
            }

            set_message("<p class='bg-success text-center'>Please check your email or spam folder for a password reset code</p>");
            redirect("index.php");



           }else{

            echo validation_errors("This emails does not exists");
           }


        }else{
            //If the token does not exists

            redirect("index.php");
        }
         //token checks

       
    }
} //post reequests




/************************************Code Validation********************************************************* */

function validate_code(){

    if(isset($_COOKIE['tmp_access_code'])){

      

            if(!isset($_GET['email']) && !isset($_GET['code'])){

                redirect("index.php");
            
            }else if(empty($_GET['email']) || empty($_GET['code'])){


                redirect("index.php");
            }else{


                if(isset($_POST['code'])){

                    $email= clean($_GET['email']);

                    $validation_code=clean($_POST['code']);

                    $sql= "SELECT id FROM users WHERE validation_code= '".escape($validation_code)."' AND email= '".escape($email)."'";
                    $result=query($sql);
                    if(row_count($result)==1){
                        
                        redirect("reset.php");

                    }else{

                        echo validation_errors("Sorry Wrong Validation code");
                    }
                    

                }
            }
        


    } else{


        set_message("<p class='bg-success text-center'>Sorry your Validation Coookie has expired</p>");
        redirect("recover.php");
    }
}