import { Component, OnInit } from '@angular/core';
import { routerTransition } from '../../router.animations';
import { ActivatedRoute } from '@angular/router';
import { trigger, state, style, transition, animate } from '@angular/animations';

@Component({
  selector: 'app-product',
  templateUrl: './product.component.html',
  styleUrls: ['./product.component.scss'],
  animations: [
    routerTransition(),
    trigger('fadeInOut', [
      transition(':enter', [
        style({ opacity: 0, transform: 'translateY(20px)' }),
        animate('400ms ease-in', style({ opacity: 1, transform: 'translateY(0)' }))
      ]),
      transition(':leave', [
        animate('400ms ease-out', style({ opacity: 0, transform: 'translateY(20px)' }))
      ])
    ])
  ]
})
export class ProductComponent implements OnInit {
  selectedProduct: number = 1;

  private productData = {
    1: { name: 'High Frequency Transformer', category: 'Electrical Transformer', image: 'assets/images/products/high-frequency-transformer.jpg' },
    2: { name: 'Voltage Stabilizer Transformer', category: 'Electrical Transformer', image: 'assets/images/products/voltage-stabilizer-transformer.jpg' },
    3: { name: 'Transformer For UPS & Inverter', category: 'Electrical Transformer', image: 'assets/images/products/transformer-for-ups.jpg' },
    4: { name: 'Transformer For Stabilizer', category: 'Electrical Transformer', image: 'assets/images/products/transformer-for-stabilizers.jpg' },
    5: { name: 'AC DC Adaptor Transformer', category: 'Electrical Transformer', image: 'assets/images/products/ac-dc-adapter-transformers.jpg' },
    6: { name: 'PCB Mountable Transformer', category: 'Electrical Transformer', image: 'assets/images/products/pcb-mountable-transformer.jpg' },
    7: { name: 'PCB Mountable Pulse Transformer', category: 'Electrical Transformer', image: 'assets/images/products/pcb-mountable-pulse-transformer.jpg' },
    8: { name: 'Power SMPS', category: 'Power Solutions', image: 'assets/images/products/power-smps.jpg' },
    9: { name: 'CC LED Driver', category: 'Power Solutions', image: 'assets/images/products/cc-led-driver.jpg' },
    10: { name: 'Electric Field Coil', category: 'Coils & Chokes', image: 'assets/images/products/electric-field-coils.jpg' },
    11: { name: 'Electric Chokes', category: 'Coils & Chokes', image: 'assets/images/products/electric-chokes.jpg' }
  };

  constructor(private route: ActivatedRoute) { }

  ngOnInit(): void {
    this.route.params.subscribe(params => {
      this.selectedProduct = parseInt(params['id'] ?? 1);
    });
  }

  setProduct(product: number) {
    this.selectedProduct = product;
  }

  toggleCategory(category: string) {
    // Toggle category expansion functionality can be added here
    console.log('Category toggled:', category);
  }

  getProductName(productId: number): string {
    return this.productData[productId]?.name || 'Product';
  }

  getProductCategory(productId: number): string {
    return this.productData[productId]?.category || 'Category';
  }

  getProductImage(productId: number): string {
    return this.productData[productId]?.image || 'assets/images/placeholder.jpg';
  }
}
