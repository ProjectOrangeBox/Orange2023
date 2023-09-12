Helpers are globally avaiable functions

These should be wrapped in 

if (!function_exists(...))

so they can be mocked (ie. replaced for testing)
