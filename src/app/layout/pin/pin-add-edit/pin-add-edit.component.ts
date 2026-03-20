import { Component, OnInit } from '@angular/core';
import { AppService }  from '../../../app.service';
import { ToastrService } from 'ngx-toastr';
import { Router } from '@angular/router';

@Component({
  selector: 'app-pin-add-edit',
  templateUrl: './pin-add-edit.component.html',
  styleUrls: ['./pin-add-edit.component.scss']
})
export class PinAddEditComponent implements OnInit {
  form = {
    companyName: '0',
    pinName: '0',
    model: '',
    description: '',
    pinNo: '0',
    pinType: '0',
    inductance: '',
    rate: '',
    comment: '',
    imageType: '0',
    formPinData: []
  };
  formPinDataList: any[] = [];
  //pinDataRows: any[] = [];
  newDynamic: any = {};
  companyNameList: any;
  pinNameList: any;
  pinNosList: any;
  pinTypeList: any;
  imageTypeList: any;

  constructor(private appService :AppService, private toastrService: ToastrService,
    private router: Router) { }

  ngOnInit(): void {
    this.bindDropdown();
    if(this.formPinDataList.length == 0){
      this.newDynamic = { pinConfigOrder: 1, fromPin: "0", toPin: "0", turns: 0, gauge: "0#" };
      this.formPinDataList.push(this.newDynamic);
      //this.formPinDataList = this.pinDataRows
    }
  }

  onAddRow(index: any) {
    //this.pinDataRows = this.formPinDataList;
    //console.log(this.pinDataRows);
    this.newDynamic = { pinConfigOrder: 1, fromPin: "0", toPin: "0", turns: 0, gauge: "0#" };
    this.formPinDataList.push(this.newDynamic);
    //this.formPinDataList = this.pinDataRows
    console.log(this.formPinDataList);
  }

  onRemoveRow(rowIndex:number) {
    if (rowIndex > -1) {
      this.formPinDataList.splice(rowIndex, 1);
    }
  }

  createItemPinData() {
    return { pinConfigOrder: 1, fromPin: "0", toPin: "0", turns: 0, gauge: "0#" };
  }

  onSubmit(){
    console.log(this.form);
    console.log(this.formPinDataList)
    this.form.formPinData = this.formPinDataList
    this.appService.addEditPin(this.form).subscribe(
      response => {
        if (response.result != null) {
        }        
      },
      error => {
        console.log(error);
        this.toastrService.error('Message Error!', 'There is network error, please try after sometime.');
      });
  }

  bindDropdown(){
    this.appService.getDropdownData().subscribe(
      response => {
        if (response.result != null) {
          this.companyNameList = response.result.companyName;
          this.pinNameList = response.result.pinName;
          this.pinNosList = response.result.pinNos;
          this.pinTypeList = response.result.pinType;
          this.imageTypeList = response.result.imageType;
        }        
      },
      error => {
        console.log(error);
        this.toastrService.error('Message Error!', 'There is network error, please try after sometime.');
      });
  }

  onReset() {
    
  }
}