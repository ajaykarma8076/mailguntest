<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Mailgun\Mailgun;

class MailGunController extends Controller
{
    public function showPage()
    {
        $templates = $this->get_templates();
        return view('welcome',compact('templates'));
    }

    private function get_templates() {

        $domain = env('MAILGUN_DOMAIN');
        $private_key = env('MAILGUN_PRIVATE_KEY');
        
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, 'api:'.$private_key);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_URL, 'https://api.mailgun.net/v3/'.$domain.'/templates');

        $result = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($result);

        $templates = [];
        foreach($data->items as $template)
        {
            array_push($templates, $template->name);
        }

        return $templates;
    }

    public function send(Request $request)
    {
        $request->validate([
            'template' => 'required',
            'first_name' => 'required',
            'last_name' => 'required'
        ]);

        $first_name = $request->first_name;
        $last_name = $request->last_name;
        $template = $request->template;
        $this->sendEmail($first_name,$last_name,$template);

        return back()->with('success','Mail Sent');
    }

    private function sendEmail($first_name,$last_name,$template)
    {
        $domain = env('MAILGUN_DOMAIN');
        $private_key = env('MAILGUN_PRIVATE_KEY');

        $mgClient = new Mailgun($private_key);

        # Make the call to the client.
        $result = $mgClient->sendMessage("$domain",
            array(
                'from' => 'postmaster@sandboxdd4deb9f75e64d77b3fc4bfd915b8b73.mailgun.org',
                'to' => 'ajayvishwakarma8076@outlook.com',
                'subject' => 'Hello '.$first_name.' '.$last_name,
                'template' => $template,
                'h:X-Mailgun-Variables' => '{"first_name": "$first_name","last_name": "$last_name"}'
            )
        );
        
    }

    public function sendTemplateNamesAPI()
    {
        $templates = $this->get_templates();
        return response()->json(['success'=>1,'templates'=>$templates]);
    }

    public function sendEmailAPI()
    {
        $validator = $request->validate([
            'template' => 'required',
            'first_name' => 'required',
            'last_name' => 'required'
        ]);

        if($validator->fails()){
            return response()->json(['success'=>0,'errors'=>$validator->errors()]);       
        }

        $first_name = $request->first_name;
        $last_name = $request->last_name;
        $template = $request->template;
        $this->sendEmail($first_name,$last_name,$template);

        return response()->json(['success'=>1,'message'=>'Mail Sent']);
    }
}
