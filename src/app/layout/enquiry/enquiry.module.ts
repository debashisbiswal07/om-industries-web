import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { PageHeaderModule } from './../../shared';
import { EnquiryComponent } from './enquiry.component';
import { EnquiryRoutingModule } from './enquiry-routing.module';
import { FormsModule } from '@angular/forms';
import { AgGridModule } from 'ag-grid-angular';


@NgModule({
  declarations: [EnquiryComponent],
  imports: [
    CommonModule, PageHeaderModule, EnquiryRoutingModule, FormsModule, AgGridModule
  ],
  exports: [EnquiryComponent]
})
export class EnquiryModule { }
