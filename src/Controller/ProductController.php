<?php
namespace App\Controller;

use Twig\Environment;
use App\Entity\Product;
use App\Entity\ProductSearch;
use App\Form\ProductSearchType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;
use Dompdf\Dompdf;
use Dompdf\Options;

class ProductController extends AbstractController
{

    public function __construct(ProductRepository $productRep, EntityManagerInterface $em)
    {
        $this->productRep = $productRep;
        $this->em = $em;
    }

    /**
     * @Route("/", name="product.index")
     * 
     * @return HttpFoundationResponse
     */
    public function index(PaginatorInterface $paginatorInterface, Request $request):HttpFoundationResponse
    {
        $search = new ProductSearch();
        $form = $this->createForm(ProductSearchType::class, $search);
        $form->handleRequest($request);

        $products = $paginatorInterface->paginate(
            $this->productRep->findSearchedQuery($search),
            $request->query->getInt('page', 1),
            10
        );
        


        return $this->render('product/index.html.twig', [
            'products' => $products,
            'form' => $form->createView(),
            ]);
    } 

    /**
     * @Route("/products/{slug}-{id}", name="product.show", requirements={"slug": "[a-zA-Z0-9\-]*"})
     * @param [type] $slug
     * @param [type] $id
     * @return Response
     */
    public function show(Product $product, string $slug):Response
    {
        if($product->getSlug() !== $slug){
            return $this->redirectToRoute('product.show', [
                'id' => $product->getId(),
                'slug' => $product->getSlug()
            ], 301);
        }
        return $this->render('product/show.html.twig', [
            'product' => $product,
            'dangerPictograms' => $product->getDangerPictograms(),
            'obligationPictograms' => $product->getObligationPictograms(),
            'update' => $product->getUpdatedAt()->format('d/m/Y'),
        ]);
    }

    /**
     * @Route("/products/{slug}-{id}/securityForm", name="product.show.securityForm", requirements={"slug": "[a-zA-Z0-9\-]*"})
     *
     * @param Product $product
     * @param string $slug
     * @return Response
     */
    public function showSeucityForm(Product $product, string $slug): Response
    {
        if($product->getSlug() !== $slug){
            return $this->redirectToRoute('product.show', [
                'id' => $product->getId(),
                'slug' => $product->getSlug()
            ], 301);
        }

        return $this->render('product/securityForm.html.twig', [
            'product' => $product,
        ]);

    }

    /**
     * @Route("/products/{slug}-{id}/download", name="product.show.download", requirements={"slug": "[a-zA-Z0-9\-]*"})
     *
     * @param Product $product
     * @param string $slug
     * @return Response
     */
    public function downloadSummarySheet(Product $product, string $slug): Response
    {
        if($product->getSlug() !== $slug){
            return $this->redirectToRoute('product.show', [
                'id' => $product->getId(),
                'slug' => $product->getSlug()
            ], 301);
        }

        // Configure Dompdf according to your needs
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        
        // Instantiate Dompdf with our options
        $dompdf = new Dompdf($pdfOptions);

        $dompdf->set_base_path("/www/public/css/");
        
        // Retrieve the HTML generated in our twig file
        $html = $this->renderView('product/summarySheetToPDF.html.twig', [
            'product' => $product,
            'dangerPictograms' => $product->getDangerPictograms(),
            'obligationPictograms' => $product->getObligationPictograms(),
            'update' => $product->getUpdatedAt()->format('d/m/Y'),
            'title' => "Fiche résumé ".$product->getFrenchName(),
        ]);
        
        // Load HTML to Dompdf
        $dompdf->loadHtml($html);
        
        // (Optional) Setup the paper size and orientation 'portrait' or 'portrait'
        $dompdf->setPaper('A4', 'portrait');

        // Render the HTML as PDF
        $dompdf->render();

        // Output the generated PDF to Browser (inline view)
        $dompdf->stream($product->getFrenchName()."_fiche_resume.pdf", [
            "Attachment" => false
        ]);


    }



}