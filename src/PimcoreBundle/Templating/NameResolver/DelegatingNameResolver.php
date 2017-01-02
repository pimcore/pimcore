<?php

namespace PimcoreBundle\Templating\NameResolver;

class DelegatingNameResolver implements NameResolverInterface
{
    /**
     * @var NameResolverInterface[]
     */
    protected $resolvers = [];

    /**
     * @param NameResolverInterface $resolver
     * @return $this
     */
    public function addResolver(NameResolverInterface $resolver)
    {
        $this->resolvers[] = $resolver;

        return $this;
    }

    /**
     * Resolve helper name
     *
     * @param $name
     * @return string
     */
    public function resolve($name)
    {
        foreach ($this->resolvers as $resolver) {
            $resolverResult = $resolver->resolve($name);
            if ($resolverResult !== $name) {
                return $resolverResult;
            }
        }

        return $name;
    }
}
