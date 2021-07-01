settings:
    memory_limit: 1024M
    bootstrap: _bootstrap.php
    colors: true
namespace: Pimcore\Tests
actor: Tester
paths:
    tests: tests
    log: tests/_output
    data: tests/_data
    support: tests/_support
    envs: tests/_envs
params:
    - env
extensions:
    enabled:
        - Codeception\Extension\RunFailed
