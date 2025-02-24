<?php

namespace App\Controller;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProductController extends AbstractController
{

    /**
     * @var TranslatorInterface The used translator interface
     */
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }
    /** GET methods */

    #[Route(
        '/{_locale}/product',
        name: 'product_index',
        requirements: [
            '_locale' => '%supported_locales%'
        ],
        methods: ['GET']
    )]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $products = $entityManager->getRepository(Product::class)->findAll();

        return $this->json($products, 200, [], [
            'groups' => ['product.index']
        ]);
    }

    #[Route(
        '/{_locale}/product/{id}',
        name: 'product_show',
        requirements: [
            'id' => Requirement::DIGITS,
            '_locale' => '%supported_locales%'
        ],
        methods: ['GET']
    )]
    public function show(EntityManagerInterface $entityManager, int $id): Response
    {
        $product = $entityManager->getRepository(Product::class)->find($id);

        if (!$product) {
            throw $this->createNotFoundException(
                $this->translator->trans("product_not_found", ["id" => $id], "errors")
            );
        }

        return $this->json($product, 200, [], [
            'groups' => ['product.index', 'product.detail']
        ]);
    }
    
    /** POST methods */

    #[Route(
        '/{_locale}/product',
        name: 'product_create',
        requirements: [
            '_locale' => '%supported_locales%'
        ],
        methods: ['POST']
    )]
    public function create(
        Request $request,
        #[MapRequestPayload(
            acceptFormat: "json",
            serializationContext: [
                'groups' => ['product.create']
            ]
        )]
        Product $product,
        EntityManagerInterface $em
    ) : Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $product->setCreatedAt(new \DateTimeImmutable());
        $product->setUpdatedAt(new \DateTimeImmutable());
        $em->persist($product);
        $em->flush();

        return $this->json($product, 201, [], [
            'groups' => ['product.index', 'product.detail']
        ]);
    }

    /** PATCH methods */

    #[Route(
        '/{_locale}/product/{id}',
        name: 'product_update',
        requirements: [
            'id' => Requirement::DIGITS,
            '_locale' => '%supported_locales%'
        ],
        methods: ['PATCH']
    )]
    public function update(
        Request $request,
        #[MapRequestPayload(
            acceptFormat: "json",
            serializationContext: [
                'groups' => ['product.update']
            ]
        )]
        Product $newData,
        int $id,
        EntityManagerInterface $em
    ) : Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $product = $em->getRepository(Product::class)->find($id);
        if (!$product) {
            throw $this->createNotFoundException(
                $this->translator->trans("product_not_found", ["id" => $id], "errors")
            );
        }

        $product->mergeNewData($newData);
        $em->persist($product);
        $em->flush();

        return $this->json($product, 200, [], [
            'groups' => ['product.index', 'product.detail']
        ]);
    }

    /** DELETE methods */

    #[Route(
        '/{_locale}/product/{id}',
        name: 'product_update',
        requirements: [
            'id' => Requirement::DIGITS,
            '_locale' => '%supported_locales%'
        ],
        methods: ['DELETE']
    )]
    public function delete(
        Request $request,
        int $id,
        EntityManagerInterface $em
    ) : Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $product = $em->getRepository(Product::class)->find($id);
        if (!$product) {
            throw $this->createNotFoundException(
                $this->translator->trans("product_not_found", ["id" => $id], "errors")
            );
        }

        $em->remove($product);
        $em->flush();

        return $this->json($product, 200, [], [
            'groups' => ['product.index', 'product.detail']
        ]);
    }
}