# UcsfLdapOrm

A Symfony bundle that provides an ORM for LDAP.

Oct 1, 2020. It's been quite a while that UcsfLdapOrm has been stuck at Symfony 3.3 compatibiliy, but that is about to change. I am finally in the process of upgrading and moving directly to Symfony 5.whatever the latest minor version maybe at the time you are reading this. 

The initial effort has been to conform to all things Symfony 5, including the most recent version of Doctrine that it uses. The new version is currently on the "v5" branch and will have a new 5.x.x version -- a 2 major version jump. This is largely an attempt to keep the bundle's major version in step with Symfony's.  After this initial push, and perhaps somewhat during, I'll be cleaning things up and doing some reorganizing and refactoring. While the first years of life for this bundle were spent enhancing Mathieu Goulin's original code without wanting to get into the business of a full on refactoring, the time has come for a change.

My goal is to have 5.1.0 ready and merged into master by Christmas 2020.

The original README:

This code was originally based upon <a href="https://github.com/matgou">Mathieu Goulin</a>'s <a href="https://github.com/matgou/GorgLdapOrmBundle">GorgLdapOrmBundle</a>. We are forever indebted to him for providing an excellent base for the work we've continued at UCSF IT Identity & Access Management. Originally we forked GorgLdapOrmBundle but, as our development continued to diverge and added new functionality, we came to the point where it was time to strike out on our own. The UcsfLdapOrm repo was created as that fresh start.

What's changed and/or been added so far:

* Added the <code>LdapEntity</code> class. This is a Symfony entity which represents the <code>top</code> LDAP object class.
* Added many subclasses of <code>LdapEntity</code> to describe the object classes from <code>top</code> down to  <code>InetOrgPerson</code>.
* Added <code>Repository::filterByComplex()</code> which gives the entity manager/repository the ability to filter with custom constructed, complex boolean logic. (See code comment API documentation for details.)
* Removed the dependency upon <a href="https://github.com/r1pp3rj4ck">r1pp3rj4ck</a>'s <a href="https://github.com/r1pp3rj4ck/TwigstringBundle">TwigstringBundle</a> and replaced it with Symfony 2.6+'s ability to use Twig's new-ish string-as-template functionality.

## Installation

Requires PHP5.5+ and Symphony 2.7+

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
parameters:
    some_ldap_server:
        uri: ldaps://ldap.example.com
        use_tls: true
        bind_dn: cn=admin,dc=example,dc=com
        password: shhhItsASecret
        password_type: plaintext
    ucsfldaporm_test: false
```

* __uri__: The URI you need for connecting to the LDAP service.
* __use_tls__: 'true' or 'false' to decide on connecting with TLS
* __bind_dn__: The DN for binding to the LDAP service
* __password__: The password associated with the given bind DN
* __password_type__: `sha1` or `plaintext`. I use plaintext when the URI is LDAPS.

#### Dependency injection for LDAP Entity Managers and Services

```
services:
    myldap_entity_manager:
        class: Ucsf\LdapOrmBundle\Ldap\LdapEntityManager
        public: true
        arguments: ["@logger", "@annotation_reader", "%some_ldap_server%"]
    comexample_person_service:
        class: MyBundle\ComExamplePersonService
        arguments: [ @myldap_entity_manager ]
```

#### Creating Entities (usually to represent an object class)

```
/**
 * Represents a ComExamplePerson object class, which is a subclass of InetOrgPerson
 * 
 * @ObjectClass("comExamplePerson")
 * @SearchDn("ou=people,dc=example,dc=come")
 * @Dn("uid={{ entity.uid }},ou=people,dc=example,dc=com")
 */
class ComExamplePerson extends InetOrgPerson
{
    /**
     * @Attribute("comExampleFavoriteIceCreamFlavor")
     * @Must()
     * @ArrayField()
     * 
     * The @Attribute annotation relates the $comExampleFavoriteIceCreamFlavor member variable to the
     * 'comExampleFavoriteIceCreamFlavor' attribute within the ComExamplePerson object class in LDAP. 
     * You don't have to name the PHP variable the same as your attribute name, but it helps to be
     * consistent in this way.
     *
     * The @Must annotation requires this attribute to not be empty when persisting back to LDAP. If 
     * a @Must requirement is not satisfied, attempting to persist the entry will throw
     * a MissingMustAttributeException.
     *
     * The @ArrayField aannotation tells the LDAP Entity Manager, repositories and services to treat
     * this attribute as a multi-value LDAP field. This is unfortunately backwards from LDAP's default
     * to multi-value an attribute. Baring miracles (i.e. finding the time), this will probably not be "fixed".
     *
     */
    protected $comExampleFavoriteIceCreamFlavor;
    
    ...
    
    public function getComExampleFavoriteIceCreamFlavor() {
        return $this->comExampleFavoriteIceCreamFlavor;
    }
    
    public function setComExampleFavoriteIceCreamFlavor($comExampleFavoriteIceCreamFlavor) {
        $this->comExampleFavoriteIceCreamFlavor = $comExampleFavoriteIceCreamFlavor;
    }
    
    ...
}
```

#### Coding the Service

```
    class ComExamplePersonService {

    protected $comExamplePersonRepository;

    public function __construct(LdapEntityManager $entityManager) {
        // Make a repo for ComExamplePerson entities
        $this->comExamplePersonRepository = $entityManager->getRepository(ComExamplePerson::class);
        // Make a another repo for SomethignElse entities (just another example...)
        $this->somethingElseRepository = $entityManager->getRepository(SomethingElse::class);
        ...
    }
            
    public function getPersonByUid($uid, $includeAddress = false, $attributes = null) {
        $person = $this->comExampePersonRepository->findByUid($uid, $attributes);
        ...
        return $person;
    }
        
```

#### A Controller... to Round it Out

````
    class PeopleController extends Controller {

        /**
         * @Route("/person/detail/{uid}")
         * @Template()
         */
        public function detailAction(Request $request, $uid)
        {
            $comExamplePersonService = $this->get('comexample_person_service');
            $person = $comExamplePersonService->getPersonByUid($uid);
            ...
            return array('person' => $person);
        }
````


## To do

1. ~~Remove need for generic LDAP config~~
2. ~~Configuration documentation~~
3. ~~Development example~~
4. Rewrite test suite (In progress...)
5. Remove deprecated search results iterator
