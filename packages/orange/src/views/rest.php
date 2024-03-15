<?php

$response->responseCode($statusCode)->contentType($contentType)->write(json_encode($json));
