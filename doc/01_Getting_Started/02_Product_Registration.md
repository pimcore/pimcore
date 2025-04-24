# Product Registration
To ensure secure and compliant usage, Pimcore requires product registration before it can be installed or used. 
This mechanism is designed to validate each Pimcore instance. 

The product registration is mandatory for running Pimcore. Without a valid product key, installation is not 
possible, and the Symfony container cannot be built (relevant for upgrade of existing installations).

This validation ensures the integrity of the installation and compliance with Pimcore licensing.

### Instance Identification
Every Pimcore project instance must be registered individually. The registration is based on an instance ID and 
the `pimcore.encryption.secret` which are generated during installation.
A `hash_hmac('sha256', $this->instanceIdentifier, $secret);` is used as identifier for the registration process.
This ensures a unique identifier, but also makes sure no secret data is leaked during the registration process.

Different environments of an instance such as development, staging, testing, and production can share the same product 
key — as long as they share the same instance ID and encryption secret.

But one Product Key must not be used for multiple Pimcore instances!

### Registration Mechanism
The registration is a one-time process and done via the [Pimcore Product Registration Form](https://license.pimcore.com/register). 
Your Pimcore instance will provide you with a deeplink including the instance ID when asking for the product key.

Open the link, follow the instructions, generate a Product Key and apply it to your instance.

### Adding the Product Key
#### During Installation Process
During a fresh installation of Pimcore, the Product Key is required. 
It can be entered manually via a prompt or optionally also via parameters or environment variables (for automated installations). 

When doing manually, the prompt provides you with a deeplink to the registration form including the instance ID.

When using parameters or environment variables, make sure encryption secret, instance id and product key match 
with each other. 

After installation, the encryption secret, instance id, as well as the product key will be added to the configuration  
in `config/local/product_registration.yaml`.
It is recommended to move them to environment variables when deploying the system to multiple stages.

#### During Upgrade or Applying Manually
If you're upgrading from an older Pimcore version (or if you have changed the encryption secret of your instance), 
a product registration error will be thrown during container build.

Follow the registration link in the error message to generate a valid product key and add it to the symfony configuration
as described in the product registration process. 

In general, it is recommended to use env vars to handle secret and product key. 

### Verification Logic
The product key is validated locally via private-public key validation. It does a signature verification as well as 
a check for matching instance ID. 

The checks are performed:
- during installation of Pimcore.
- during the build of the Symfony container.


### Tips & Tricks

- For CI pipelines or automated testing, you can pass both the secret, instance id, and the product key to the 
  installer using parameters and environment variables. This enables reproducible, automated installations with static 
  instance secrets and product keys.
- For updating product registration of an existing installation, just change the instance identifier (symfony 
  configuration `pimcore.product_registration.instance_identifier`) and restart the process (build container, 
  follow link in error message, generate new product key, apply it to the configuration).
