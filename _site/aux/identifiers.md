# Identifiers

## Theoretical considerations

We can distinguish two broad indentifier types:

* Content state identifiers. Identify an exact state of an entity.
  * If the state changes, the identifier isn't valid for the new state any more.
  * Such identifier should point to exactly same thing for its whole lifespan.
  * The less semantics such an identifier has, the better.
    This is because semantics injects _abstract concepts_ into the identifier which makes it likely to break assumptions listed above.
  * A good example is a git commit hash.
* Abstract concept identifiers. Identify everything which doesn't have a 
  * 


[PIDs](https://en.wikipedia.org/wiki/Persistent_identifier)
