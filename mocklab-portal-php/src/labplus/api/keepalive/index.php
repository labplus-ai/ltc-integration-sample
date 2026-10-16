<?php
// Called by labplus.js while the patient is active in the LabTest Checker iframe: opening the session
// is enough to keep it alive, so the patient is not logged out of the portal while answering the questionnaire.
session_start();
http_response_code(204);
