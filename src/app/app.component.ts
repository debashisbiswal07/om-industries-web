import { Component, OnInit } from '@angular/core';
import { Router, NavigationEnd } from '@angular/router';
import { ToastrService } from 'ngx-toastr';
import { NgxSpinnerService } from "ngx-spinner";
import { LoaderService } from './shared/services/loader.service';
import { filter } from 'rxjs/operators';
import { ColDef } from 'ag-grid-community';

@Component({
    selector: 'app-root',
    templateUrl: './app.component.html',
    styleUrls: ['./app.component.scss']
})
export class AppComponent implements OnInit {
    constructor(
        private toastrService: ToastrService,
        private spinner: NgxSpinnerService,
        private loader: LoaderService,
        private router: Router
    ) {}

    ngOnInit() {
        // Scroll to top on navigation
        this.router.events
            .pipe(filter(event => event instanceof NavigationEnd))
            .subscribe(() => {
                window.scrollTo(0, 0);
            });

        this.loader.isLoading.subscribe(res => {
            if(res) {
                this.spinner.show();
            }
            else {
                this.spinner.hide();
            }
        })
    }
     columnDefs: ColDef[] = [
      { field: 'make' },
      { field: 'model' },
      { field: 'price' },
    ];
  
    rowData = [
      { make: 'Toyota', model: 'Celica', price: 35000 },
      { make: 'Ford', model: 'Mondeo', price: 32000 },
      { make: 'Porsche', model: 'Boxter', price: 72000 },
    ];
}
