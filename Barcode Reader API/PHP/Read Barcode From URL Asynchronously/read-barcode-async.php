<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cloud API asynchronous "Barcode Reader" job example (allows to avoid timeout errors).</title>
</head>
<body>

<?php 

// Cloud API asynchronous "Barcode Reader" job example.
// Allows to avoid timeout errors when processing huge or scanned PDF documents.


// The authentication key (API Key).
// Get your own by registering at https://app.pdf.co/documentation/api
$apiKey = "***************************************";

// Direct URL of source file (image or PDF) to search barcodes in. Check another example if you need to upload a local file to the cloud.
$sourceFileUrl = "https://bytescout-com.s3.amazonaws.com/files/demo-files/cloud-api/barcode-reader/sample.pdf";
// Comma-separated list of barcode types to search. 
// See valid barcode types in the documentation https://app.pdf.co/documentation/api/1.0/barcode/read_from_url.html
$barcodeTypes = "Code128,Code39,Interleaved2of5,EAN13";
// Comma-separated list of page indices (or ranges) to process. Leave empty for all pages. Example: '0,2-5,7-'.
$pages = "";


// Prepare URL for `Barcode Reader` API call
$url = "https://api.pdf.co/v1/barcode/read/from/url";

// Prepare requests params
$parameters = array();
$parameters["types"] = $barcodeTypes;
$parameters["pages"] = $pages;
$parameters["url"] = $sourceFileUrl;
$parameters["async"] = true; // (!) Make asynchronous job

// Create Json payload
$data = json_encode($parameters);

// Create request
$curl = curl_init();
curl_setopt($curl, CURLOPT_HTTPHEADER, array("x-api-key: " . $apiKey, "Content-type: application/json"));
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

// Execute request
$result = curl_exec($curl);
echo $result . "<br/>";

if (curl_errno($curl) == 0)
{
    $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    
    if ($status_code == 200)
    {
        $json = json_decode($result, true);
        
        if ($json["error"] == false)
        {
            // URL of generated JSON file that will available after the job completion
            $resultFileUrl = $json["url"];
            // Asynchronous job ID
            $jobId = $json["jobId"];
            
            // Check the job status in a loop
            do
            {
                $status = CheckJobStatus($jobId, $apiKey); // Possible statuses: "working", "failed", "aborted", "success".
                
                // Display timestamp and status (for demo purposes)
                echo "<p>" . date(DATE_RFC2822) . ": " . $status . "</p>";
                
                if ($status == "success")
                {
                    // Display link to JSON file with information about decoded barcodes
                    echo "<div><h2>Conversion Result:</h2><a href='" . $resultFileUrl . "' target='_blank'>" . $resultFileUrl . "</a></div>";
                    break;
                }
                else if ($status == "working")
                {
                    // Pause for a few seconds
                    sleep(3);
                }
                else 
                {
                    echo $status . "<br/>";
                    break;
                }
            }
            while (true);
        }
        else
        {
            // Display service reported error
            echo "<p>Error: " . $json["message"] . "</p>"; 
        }
    }
    else
    {
        // Display request error
        echo "<p>Status code: " . $status_code . "</p>"; 
        echo "<p>" . $result . "</p>"; 
    }
}
else
{
    // Display CURL error
    echo "Error: " . curl_error($curl);
}

// Cleanup
curl_close($curl);


function CheckJobStatus($jobId, $apiKey)
{
    $status = null;
    
    // Create URL
    $url = "https://api.pdf.co/v1/job/check";
    
    // Prepare requests params
    $parameters = array();
    $parameters["jobid"] = $jobId;

    // Create Json payload
    $data = json_encode($parameters);

    // Create request
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_HTTPHEADER, array("x-api-key: " . $apiKey, "Content-type: application/json"));
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

    // Execute request
    $result = curl_exec($curl);
    
    if (curl_errno($curl) == 0)
    {
        $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        if ($status_code == 200)
        {
            $json = json_decode($result, true);
        
            if ($json["error"] == false)
            {
                $status = $json["status"];
            }
            else
            {
                // Display service reported error
                echo "<p>Error: " . $json["message"] . "</p>"; 
            }
        }
        else
        {
            // Display request error
            echo "<p>Status code: " . $status_code . "</p>"; 
            echo "<p>" . $result . "</p>"; 
        }
    }
    else
    {
        // Display CURL error
        echo "Error: " . curl_error($curl);
    }
    
    // Cleanup
    curl_close($curl);
    
    return $status;
}

?>

</body>
</html>