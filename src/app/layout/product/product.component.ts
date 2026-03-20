import { Component, OnInit } from '@angular/core';
import { routerTransition } from '../../router.animations';
import { ActivatedRoute } from '@angular/router';

@Component({
  selector: 'app-product',
  templateUrl: './product.component.html',
  styleUrls: ['./product.component.scss'],
  animations: [routerTransition()]
})
export class ProductComponent implements OnInit {
  selectedProduct: number = 1;
  constructor(private route: ActivatedRoute) { }

  ngOnInit(): void {
    this.route.params.subscribe(params => {
      this.selectedProduct = parseInt(params['id'] ?? 1);
    });
  }

  setProduct(product: number) {
    this.selectedProduct = product;
  }
}
