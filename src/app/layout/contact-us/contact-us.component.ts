import { Component, OnInit } from '@angular/core';
import { ToastrService } from 'ngx-toastr';
import { Router } from '@angular/router';
import { DatePipe } from '@angular/common';
import { AuthService } from '../../../../src/app/shared/services/auth.service';
import { NgForm } from '@angular/forms';
import { AppService } from '../../app.service';

@Component({
  selector: 'app-contact-us',
  templateUrl: './contact-us.component.html',
  styleUrls: ['./contact-us.component.scss']
})
export class ContactUsComponent implements OnInit {
  captcha: string = "";
  userCaptcha: string = "";
  invalidCaptCha: boolean = false;
  form = {
    fullname: '',
    phone: '',
    email: '',
    enquiry: '',
  };

  columnHeader: any = [{ "field": "enquiryId", width: 100 }, { "field": "fullname", width: 150 }, { "field": "phone", width: 150 },
  { "field": "email", width: 200 }, { "field": "enquiry", width: 450 }, { "field": "isActive", width: 100 },
  { "field": "createdDate", width: 150 }, { "field": "createdBy", width: 150 }];

  constructor(private appService: AppService, private toastrService: ToastrService,
    private router: Router, private authService: AuthService) { }

  ngOnInit(): void {
    this.invalidCaptCha = false;
    this.generateRandomCaptcha();
  }

  dateFormat(date: string) {
    var datePipe = new DatePipe('en-US');
    return datePipe.transform(date, 'dd/MM/yyyy');
  }

  onSubmit(f: NgForm): void {
    if (this.form && this.captcha && this.userCaptcha && this.captcha === this.userCaptcha) {
      this.form.phone = this.form?.phone.toString();
      this.appService.postEqnuiry(this.form).subscribe(
        response => {
          if (response?.result?.status == true) {
            this.toastrService.success('Message Success!', 'We got your details, we will reach out soon.');
            this.router.navigate(['/dashboard']);
          }
          else {
            this.toastrService.error('Message Error!', 'There is network error, please try after sometime.');
          }
        },
        error => {
          console.log(error);
          this.toastrService.error('Message Error!', 'There is network error, please try after sometime.');
        });
    }
    else if (this.captcha && this.captcha !== this.userCaptcha) {
      this.invalidCaptCha = true;
    }
  }

  onReset(form: NgForm): void {
    form.reset();
  }

  generateRandomCaptcha() {
    let uniquechar = "";
    const randomchar = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

    // Generate captcha for length of
    // 5 with random character
    for (let i = 1; i < 5; i++) {
      uniquechar += randomchar.charAt(
        Math.random() * randomchar.length)
    }
    this.captcha = uniquechar;
  }

  reloadCaptcha() {
    this.captcha = "";
    this.userCaptcha = "";
    this.invalidCaptCha = false;
    this.generateRandomCaptcha()
  }
}
