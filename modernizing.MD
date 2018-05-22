Embedded Presentation Logic

For an example of embedded presentation logic, we can take a look at Appendix E. The code shows a page
script that has been refactored to use domain Transactions, but it still has some presentation logic entangled
within the rest of the code.

What Is The Difference Between “Presentation” and “Business” Logic?
For our purposes, “presentation logic” includes any and all code that generates output sent to the
user (such as a browser or mobile client). This includes not only echo and print but also header()
and setcookie(). Each of these generates some form of output. “Business logic,” on the other hand,
is everything else.

The key to decoupling the presentation logic from the business logic is to put the code for them into separate
scopes. The script should first perform all of the business logic, then pass the results over to the presentation
logic. When that is complete, we will be able to test our presentation logic separately from our business logic.
To achieve this separation of scope, we will move toward using a Response object in our page scripts. All of
our presentation logic will be executed from within a Response instance, instead of directly in the page script.
Doing so will provide the scope separation that we need to decouple all output generation, including HTTP
headers and cookies, from the rest of the page script.