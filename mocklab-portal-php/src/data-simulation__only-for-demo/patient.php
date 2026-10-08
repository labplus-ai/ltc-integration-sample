<?php
// The patient who logs in to the portal and everything the lab stores about them.
// All orders belong to this patient. The national ID identifies a person (like PESEL in Poland)
// and is kept by the lab system only.
// DEMO ONLY: in a real system this comes from the lab database.

return [
    'firstName' => 'John',
    'lastName' => 'Smith',
    'gender' => 'male',
    'birthDate' => '1992-10-20',
    'nationalId' => '921020-4417',
    'email' => 'john.smith@example.com',
    'phone' => '+48111222333',
];
