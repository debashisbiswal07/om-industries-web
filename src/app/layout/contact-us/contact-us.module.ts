import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { PageHeaderModule } from './../../shared';
import { ContactUsRoutingModule } from './contact-us-routing.module';
import { ContactUsComponent } from './contact-us.component';
import { FormsModule } from '@angular/forms';
import { EnquiryModule } from '../enquiry/enquiry.module';

@NgModule({
  declarations: [ContactUsComponent],
  imports: [
    CommonModule,
    PageHeaderModule,
    ContactUsRoutingModule, FormsModule, EnquiryModule
  ]
})
export class ContactUsModule { }
