import { CommonModule } from '@angular/common';
import { NgModule } from '@angular/core';
import { BrowserModule } from '@angular/platform-browser';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { AppRoutingModule } from './app-routing.module';
import { AppComponent } from './app.component';
import { AuthGuard } from './shared';
import { LanguageTranslationModule } from './shared/modules/language-translation/language-translation.module';
import { EnquiryModule } from './layout/enquiry/enquiry.module';
import { ToastrModule } from 'ngx-toastr';
//import { NoopAnimationsModule } from '@angular/platform-browser/animations';
import { NgxSpinnerModule } from "ngx-spinner";
import { HTTP_INTERCEPTORS, provideHttpClient, withInterceptors, withInterceptorsFromDi } from '@angular/common/http';
import { LoaderInterceptor } from './shared/services/loader.interceptor';
import { AgGridModule } from 'ag-grid-angular';
import { PinModule } from './layout/pin/pin.module';
import { PinAddEditModule } from './layout/pin/pin-add-edit/pin-add-edit.module';
import { AuthInterceptor } from './shared/services/auth.interceptor';
import { JwtHelperService, JwtModule } from '@auth0/angular-jwt';

export function tokenGetter() {
    return localStorage.getItem('access_token');
  }

@NgModule({
    declarations: [AppComponent],
    bootstrap: [AppComponent], imports: [CommonModule,
        BrowserModule,
        BrowserAnimationsModule,
        AgGridModule,
        LanguageTranslationModule,
        AppRoutingModule,
        EnquiryModule,
        //NoopAnimationsModule,
        NgxSpinnerModule.forRoot(),
        ToastrModule.forRoot({
            closeButton: true,
            timeOut: 15000, // 15 seconds
            progressBar: true,
        }),
        PinModule,
        PinAddEditModule,
        JwtModule.forRoot({
            config: {
                tokenGetter: tokenGetter,
                allowedDomains: ['example.com'], // Replace with your API domain
                disallowedRoutes: ['example.com/api/auth/'], // Replace with your authentication routes
            },
        })
    ],
    providers: [AuthGuard, JwtHelperService,
        { provide: HTTP_INTERCEPTORS, useClass: LoaderInterceptor, multi: true },
        provideHttpClient(withInterceptors([AuthInterceptor])),
        provideHttpClient(withInterceptorsFromDi())]
})
export class AppModule { }
