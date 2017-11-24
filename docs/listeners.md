Listeners
=======
There are 4 types of listeners defined in jade that allows you to easily manipulate the behaviour of a call.

* ExceptionListener: All the calls in JsonApiController are wrapped in a try catch and if any exception happens the exception listeners are called. 1 listener can return a list of errors which stop the propagation of the exception and then the errors are rendered in the response.
* RequestListener: As the name indicates you can list to every request and receive the corresponding parsed data in the listener.
* ResponseListener: The same as request listener but called right before the response is sent back to the client. Future feature are planned for this listener
* CreateListener: A listener for when a resource is being created.
* UpdateListener: A listener for when a resource is being updated.
* DeleteListener: A listener for when a resource is being deleted.

Examples of usage for Create, Update and Delete listeners
 * You can change the resource before it's saved
 * Prevent the action
 * Do something after saving the resource(send an email).

How to use
---------
Just create a service that implements 1 or more of the interfaces and tag it with `trivago_jade.listener`. The bundle then automatically loads them into the manager depending on which interface you have implemented.
