# UcsfLdapOrm

A Symfony bundle that provides ORM over LDAP.

This code was originally based upon <a href="https://github.com/matgou">Mathieu Goulin</a>'s <a href="https://github.com/matgou/GorgLdapOrmBundle">GorgLdapOrmBundle</a>. We are forever indebted to him for providing an excellent base for the work we've continue at UCSF Identity & Access Management. Originally we forked GorgLdapOrmBundle, but as our development continued to diverge and build new functionality we came to the point where it was time to strike out on our own. The UcsfLdapOrm repo was created as that fresh start.

What's changed and/or been added so far:

* Added the <code>LdapEntity</code> class. This is a Symfony entity which represents the <code>top</code> LDAP object class.
* Added added many subclasses of <code>LdapEntity</code> to describe the object classes from <code>top</code> to  <code>InetOrgPerson</code>.
* Added <code>Repository::filterByComplex()</code> which gives the entity manager/repository the ability to filter with custom constructed, complex boolean logic.
* Removed the dependency upon <a href="https://github.com/r1pp3rj4ck">r1pp3rj4ck</a>'s <a href="https://github.com/r1pp3rj4ck/TwigstringBundle">TwigstringBundle</a> and replaced it with Symfony 2.6+'s ability to use Twig's new-ish string-as-template functionality.

## Installation

* Add to composer.json
 * <code>"ucsf/ldaporm": "dev-master"</code>
* Add the bundle to AppKernel.php
 * <code>new Ucsf\LdapOrmBundle\UcsfLdapOrmBundle()</code>
* Install using composer
 * <code>$ composer update ucsf/ldaporm-bundle</code>

## Documentation

### Develop with UcsfLdapOrm

#### Configure an LDAP service in config.yml

```
myldap_service:
    connection:
        uri: ldaps://ldap.example.com
        use_tls: true
        bind_dn: cn=admin,dc=example,dc=com
        password: shhhItsASecret
        password_type: plaintext
```

* __uri__: The URI you need for connecting to the LDAP service.
* __use_tls__: 'true' or 'false' to decide on connecting with TLS
* __bind_dn__: The DN for binding to the LDAP service
* __password__: The password associate with the given bind DN
* __password_type__: sha1, plaintext. I use plaintext when the URI is LDAPS.

#### Dependency injection for LDAP Entity Managers and Services

```
services:
    myldap_entity_manager:
        class: Ucsf\LdapOrmBundle\Ldap\LdapEntityManager
        arguments: ["@logger", "@annotation_reader", "%myldap_service%"]
    myorgperson_service:
        class: MyBundle\MyOrgPersonService
        arguments: [ @myldap_entity_manager ]
```

#### Creating Entities (usually to represent an object class)

```
/**
 * Represents a MyPerson object class, which is a subclass of InetOrgPerson
 * 
 * @ObjectClass("myPerson")
 * @SearchDn("ou=people,dc=example,dc=come")
 * @Dn("uid={{ entity.uid }},ou=people,dc=example,dc=com")
 */
class MyPerson extends InetOrgPerson
{
    /**
     * @Attribute("thing")
     * @Must()
     * @ArrayField()
     * 
     * The @Attribute annotation relates the $thing member variable to the 'thing' attribute
     * with the MyPerson object class in LDAP
     *
     * The @Must annotation requires this attribute to not be empty when persisting back to LDAP.
     * 
     * The @ArrayField aannotation tells the LDAP Entity Manager, repositories and services to treat
     * this attribute as a multi-value LDAP field
     */
    protected $thing;
    
    ...
    
    public function getThing() {
        return $this->thing;
    }
    
    public function setThing($thing) {
        $this->thing = $thing;
    }
    
    ...
}


#### Coding the Service

```
class MyOrgPersonService {

    protected $myOrgPersonRepository;

    public function __construct(LdapEntityManager $entityManager) {
        // Make a repo for MyOrgPerson entities
        $this->myOrgPersonRepository = $entityManager->getRepository(MyOrgPerson::class);
        // Make a another repo for SomethignElse entities
        $this->somethingElseRepository = $entityManager->getRepository(SomethingElse::class);
        ...
    }
            
    public function getPersonByUid($uid, $includeAddress = false, $attributes = null) {
        $person = $this->myOrgPersonRepository->findByUid($uid, $attributes);
        ...
        return $person;
    }
        
```

#### A Controller... to Round it Out

````
    class PeopleController extends Controller {

        /**
         * @Route("/person/detail/{uid}/{rest}")
         * @Template()
         */
        public function detailAction(Request $request, $uid)
        {
            $myOrgPersonService = $this->get('myorgperson_service');
            $person = $myOrgPersonService->getPersonByUid($uid);
            ...
            return array('person' => $person);
        }
````

## To do

1. ~~Remove need for generic LDAP config~~
2. Configuration documentation
3. Development example
4. Rewrite test suite
5. Remove deprecated search results iterator
